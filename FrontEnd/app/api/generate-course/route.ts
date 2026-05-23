import { NextRequest, NextResponse } from "next/server"
import { generatePrompt } from "@/lib/prompt-template"
import type { GenerationConfig, MoodleCourse } from "@/lib/types"
import fs from "fs"
import path from "path"

export const runtime = "nodejs"
export const maxDuration = 300 // 5 minutos

// --- HELPERS ---
async function callAI(prompt: string, model: string, maxTokens: number): Promise<string> {
  const USE_PORTKEY = true; // Hardcoded para consistência
  
  if (USE_PORTKEY) {
    const portkeyKey = process.env.PORTKEY_API_KEY;
    if (!portkeyKey) throw new Error("PORTKEY_API_KEY not configured");

    const { default: Portkey } = await import("portkey-ai");
    const portkeyConfig: any = { apiKey: portkeyKey };
    let finalModel = model;

    if (model.startsWith("@")) {
      const slug = model.substring(1);
      if (slug.includes("/")) {
        const [vKey, mName] = slug.split("/");
        portkeyConfig.virtualKey = vKey;
        finalModel = mName;
      } else {
        portkeyConfig.config = slug;
        finalModel = undefined; 
      }
    } else {
      const virtualKey = process.env.PORTKEY_VIRTUAL_KEY;
      if (virtualKey) portkeyConfig.virtualKey = virtualKey;
      else portkeyConfig.provider = "google";
    }

    const portkey = new Portkey(portkeyConfig);
    const chatCompletion = await portkey.chat.completions.create({
      model: finalModel,
      messages: [
        { role: "system", content: "És um Especialista em Desenho de Cursos Moodle. Responde apenas em JSON puro." },
        { role: "user", content: prompt }
      ],
      temperature: 0.1, // Máximo rigor
      max_tokens: maxTokens,
      response_format: { type: "json_object" } // FORÇAR MODO JSON
    });
    
    const resContent = chatCompletion.choices?.[0]?.message?.content || "";
    console.log(`[AI] Resposta recebida (${resContent.length} chars). Preview: ${resContent.substring(0, 100)}...`);
    return resContent;
  } else {
    const geminiKey = process.env.GEMINI_API_KEY;
    if (!geminiKey) throw new Error("GEMINI_API_KEY not configured");

    const cleanModel = model.startsWith("@") ? model.split("/").slice(1).join("/") : model;
    const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/${cleanModel}:generateContent?key=${geminiKey}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        contents: [{ parts: [{ text: prompt }] }],
        generationConfig: {
          temperature: 0.1,
          maxOutputTokens: maxTokens,
          responseMimeType: "application/json" // FORÇAR MODO JSON
        }
      })
    });

    const responseData = await response.json();
    if (!response.ok) throw new Error(`Gemini API error: ${response.status}`);
    const resContent = responseData.candidates?.[0]?.content?.parts?.[0]?.text || "";
    console.log(`[AI-DIRECT] Resposta recebida (${resContent.length} chars). Preview: ${resContent.substring(0, 100)}...`);
    return resContent;
  }
}

function cleanJsonString(content: string): string {
  let jsonStr = content.trim();
  
  // 1. Tentar extrair conteúdo entre blocos de código markdown
  const codeBlockMatch = jsonStr.match(/```(?:json)?\s*([\s\S]*?)```/);
  if (codeBlockMatch && codeBlockMatch[1]) {
    return codeBlockMatch[1].trim();
  }
  
  // 2. Se não houver blocos de código, procurar o primeiro '{' e o último '}'
  const firstBrace = jsonStr.indexOf('{');
  const lastBrace = jsonStr.lastIndexOf('}');
  if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
    return jsonStr.substring(firstBrace, lastBrace + 1);
  }
  
  return jsonStr;
}

export async function POST(request: NextRequest) {
  try {
    // Importação dinâmica robusta
    const pdfModule = await import("pdf-parse");
    const { PDFParse } = pdfModule as any;
    
    if (!PDFParse) {
      console.error("PDFParse class not found in module:", pdfModule);
      return NextResponse.json({ error: "Erro interno: Classe PDFParse não encontrada." }, { status: 500 });
    }
    
    const formData = await request.formData()
    const files = formData.getAll("files") as File[]
    const configStr = formData.get("config") as string
    
    if (files.length === 0 || !configStr) {
      return NextResponse.json({ error: "Missing files or config" }, { status: 400 })
    }

    const config: GenerationConfig = JSON.parse(configStr)
    const customPrompt = formData.get("customPrompt") as string
    
    // 1. Extract clean text from PDFs
    let combinedText = ""
    for (const file of files) {
      const arrayBuffer = await file.arrayBuffer()
      const buffer = Buffer.from(arrayBuffer)
      
      try {
        const parser = new PDFParse({ data: buffer });
        const result = await parser.getText();
        combinedText += `\n--- CONTENT FROM ${file.name} ---\n${result.text}\n`;
        await parser.destroy();
      } catch (pdfError) {
        console.error(`Error parsing PDF ${file.name}:`, pdfError)
        combinedText += `\n[Erro ao extrair texto de ${file.name}]\n`
      }
    }

    // 2. Build Prompt
    if (!combinedText || combinedText.trim().length < 10) {
      console.error("No text extracted from PDF");
      return NextResponse.json({ error: "O PDF parece estar vazio ou não contém texto legível." }, { status: 400 });
    }

    const isLargeCourse = combinedText.length > 25000 || config.divideInModules;
    const fileName = files[0]?.name || "documento.pdf";
    
    // CENTRALIZAÇÃO: Ler o Master Prompt com caminho robusto
    const promptPath = path.join(process.cwd(), "..", "Prompts", "PROMPT_GERACAO_CURSO.md");
    let basePrompt = "";
    
    try {
      if (fs.existsSync(promptPath)) {
        basePrompt = fs.readFileSync(promptPath, "utf-8");
      } else {
        const altPath = "/app/Prompts/PROMPT_GERACAO_CURSO.md";
        if (fs.existsSync(altPath)) {
          basePrompt = fs.readFileSync(altPath, "utf-8");
        } else {
          console.warn(`[API] Master prompt não encontrado em ${promptPath} nem ${altPath}.`);
        }
      }
    } catch (fsError) {
      console.error("Error reading master prompt file:", fsError);
    }

    // --- ESCOLHA DO MODELO BASEADA NA PROFUNDIDADE ---
    const envModelPro = process.env.PORTKEY_MODEL_PRO;
    const envModelFlash = process.env.PORTKEY_MODEL_FLASH;
    const envMaxTokens = process.env.PORTKEY_MAX_TOKENS;

    const modelPro = envModelPro || "gemini-1.5-pro";
    const modelFlash = envModelFlash || "gemini-1.5-flash";
    const selectedModel = config.depth === "Especialista Técnico" ? modelPro : modelFlash;
    const maxTokensLimit = parseInt(envMaxTokens || "32768");

    // =========================================================================
    // FASE DE PLANEAMENTO (Se for curso grande)
    // =========================================================================
    let courseModules = [];
    if (isLargeCourse) {
      console.log(`[PLANNER] Curso detetado como GRANDE (${combinedText.length} chars). A iniciar fase de planeamento...`);
      
      const plannerPrompt = `
        Analisa o seguinte texto extraído de um PDF e cria um plano de formação estruturado em módulos ou dias.
        O objetivo é dividir o conteúdo para que cada parte possa ser gerada individualmente com alta densidade.

        REGRAS:
        1. Devolve APENAS um JSON válido.
        2. Divide o curso em 3 a 7 módulos lógicos.
        3. Para cada módulo, indica o título e um resumo dos tópicos a cobrir.

        ESTRUTURA JSON ESPERADA:
        {
          "plan": [
            { "id": 1, "title": "Módulo 1: Título", "summary": "Descrição dos tópicos..." },
            ...
          ]
        }

        CONTEÚDO DO DOCUMENTO:
        ${combinedText.substring(0, 50000)}
      `;

      // Chamada rápida ao modelo Flash para o plano
      const plannerResponse = await callAI(plannerPrompt, modelFlash, maxTokensLimit);
      try {
        const planData = JSON.parse(cleanJsonString(plannerResponse));
        courseModules = planData.plan || [];
        console.log(`[PLANNER] Plano gerado com ${courseModules.length} módulos.`);
      } catch (e) {
        console.error("[PLANNER] Erro ao processar plano da IA. Usando fallback single-shot.", e);
        courseModules = [];
      }
    }

    // =========================================================================
    // CONFIGURAÇÃO DE IA E ORQUESTRAÇÃO
    // =========================================================================
    let finalCourse: MoodleCourse | null = null;

    if (courseModules.length > 0) {
      console.log(`[ORCHESTRATOR] Modo Modular ativo. A processar ${courseModules.length} módulos sequencialmente...`);
      
      const aggregatedActivities: any[] = [];
      const aggregatedQuestionBanks: any[] = [];
      let courseMetadata: any = null;

      // Calcular questões por módulo baseado no pedido do FE (Total + 10 extra)
      const targetTotalQuestions = config.numberOfQuestions + 10;
      const questionsPerModule = Math.max(2, Math.ceil(targetTotalQuestions / courseModules.length));
      console.log(`[ORCHESTRATOR] Distribuição: ${questionsPerModule} questões por módulo para atingir ~${targetTotalQuestions} total.`);

      let globalQuestionCounter = 1;

      for (const [index, module] of courseModules.entries()) {
        const moduleNum = index + 1;
        console.log(`[ORCHESTRATOR] A gerar Módulo ${moduleNum}/${courseModules.length}: ${module.title}...`);
        
        const modularPrompt = `
          ${basePrompt}
          
          ⚠️ MODO MODULAR ATIVADO: Estás a gerar APENAS a PARTE ${moduleNum} de um curso de ${courseModules.length} módulos.
          
          FOCO ATUAL: ${module.title}
          RESUMO DO CONTEÚDO: ${module.summary}
          
          REGRAS ESTRITAS PARA ESTA CHAMADA:
          1. Responde APENAS com o objeto JSON.
          2. NOMEAÇÃO: Todas as atividades (pages) DEVEM começar por "Módulo ${moduleNum}.X: [Título]".
          3. QUIZ: Estás PROIBIDO de criar atividades do tipo "quiz" neste JSON.
          4. QUESTÕES: Cria exatamente ${questionsPerModule} questões de avaliação.
          5. NUMERAÇÃO DE QUESTÕES: O nome de cada questão deve seguir a sequência global. Começa na questão nº ${globalQuestionCounter} (Ex: "Pergunta ${globalQuestionCounter}", "Pergunta ${globalQuestionCounter + 1}", etc).
          6. CONTEÚDO: Gera conteúdo denso (mínimo 500 palavras por página).
          7. INTRO/OUTRO: Não geris introduções globais ou conclusões.
          
          CONTEÚDO DO DOCUMENTO:
          ${combinedText}
        `;

        const moduleResponse = await callAI(modularPrompt, selectedModel, maxTokensLimit);
        try {
          const moduleData: MoodleCourse = JSON.parse(cleanJsonString(moduleResponse));
          
          if (index === 0) {
            courseMetadata = {
              course_name: moduleData.course_name,
              course_shortname: moduleData.course_shortname,
              source_file: moduleData.source_file,
              course_summary: moduleData.course_summary,
              image_folder: moduleData.image_folder
            };
          }

          if (moduleData.activities) {
            // Apenas páginas (filtrar qualquer quiz que a IA tenha gerado por erro)
            const modulePages = moduleData.activities.filter(act => act.type === 'page');
            aggregatedActivities.push(...modulePages);
          }
          
          if (moduleData.question_banks) {
            for (const bank of moduleData.question_banks) {
              const bankName = "Banco Global de Questões"; // Unificar tudo num só banco
              const existingBank = aggregatedQuestionBanks.find(b => b.name === bankName);
              
              // Re-numerar e limpar nomes das questões para garantir sequência perfeita
              if (moduleData.question_banks[0]?.questions) {
                moduleData.question_banks[0].questions.forEach((q: any) => {
                  const qNumStr = globalQuestionCounter.toString().padStart(2, '0');
                  q.name = `Pergunta ${qNumStr}: ${q.name.split(':').pop()?.trim() || ''}`;
                  globalQuestionCounter++;
                });
              }

              if (existingBank) {
                existingBank.questions.push(...bank.questions);
              } else {
                bank.name = bankName;
                aggregatedQuestionBanks.push(bank);
              }
            }
          }
          
          console.log(`[ORCHESTRATOR] Módulo ${moduleNum} concluído.`);
        } catch (e) {
          console.error(`[ORCHESTRATOR] Erro no Módulo ${moduleNum}.`, e);
        }
      }

      if (courseMetadata) {
        // Criar a página de Introdução Real no início
        const introPage = {
          name: "Introdução ao Curso",
          type: "page",
          content: `<div class=\"ailms-page-container\"><h1>Bem-vindo ao curso ${courseMetadata.course_name}</h1><p>${courseMetadata.course_summary}</p></div>`
        };

        // Criar o Quiz Final Único
        const finalQuiz = {
          name: "Exame Final de Avaliação",
          type: "quiz",
          intro: `Responda a este exame de ${config.numberOfQuestions} questões para validar os seus conhecimentos e obter a certificação.`,
          questions_per_page: 5,
          time_limit: config.quizDuration * 60,
          pass_grade: 8,
          question_banks: ["Banco Global de Questões"],
          random_questions: config.numberOfQuestions // Moodle vai buscar X questões aleatórias do banco total
        };

        // Criar a página de Conclusão no fim
        const conclusionPage = {
          name: "Encerramento e Próximos Passos",
          type: "page",
          content: "<div class=\"ailms-page-container\"><h1>Parabéns!</h1><p>Concluiu todos os módulos teóricos. Prossiga para o exame final.</p></div>"
        };

        finalCourse = {
          ...courseMetadata,
          activities: [introPage, ...aggregatedActivities, conclusionPage, finalQuiz],
          question_banks: aggregatedQuestionBanks
        };
      }
    }

    if (!finalCourse) {
      console.log("[ORCHESTRATOR] A usar modo Single-Shot (Padrão)...");
      const prompt = customPrompt 
        ? `${customPrompt}\n\nCONTEÚDO DO DOCUMENTO EXTRAÍDO:\n${combinedText}\n\nResponde APENAS com o JSON integral.`
        : generatePrompt(basePrompt, config, combinedText, fileName);

      const content = await callAI(prompt, selectedModel, maxTokensLimit);
      finalCourse = JSON.parse(cleanJsonString(content));
    }

    if (!finalCourse) {
      return NextResponse.json({ error: "Falha na geração do curso" }, { status: 500 });
    }

    return NextResponse.json({ course: finalCourse });

  } catch (error) {
    console.error("Critical error:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Internal Server Error" },
      { status: 500 }
    );
  }
}
