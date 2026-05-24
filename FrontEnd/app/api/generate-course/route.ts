import { NextRequest, NextResponse } from "next/server"
import { generatePrompt } from "@/lib/prompt-template"
import type { GenerationConfig, MoodleCourse } from "@/lib/types"
import fs from "fs"
import path from "path"

export const runtime = "nodejs"
export const maxDuration = 900 // 15 minutos para cursos massivos

// --- HELPERS ---
async function callAI(prompt: string, model: string, maxTokens: number, retries: number = 3): Promise<string> {
  const USE_PORTKEY = true;
  
  for (let i = 0; i < retries; i++) {
    try {
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
          temperature: 0.1, 
          max_tokens: maxTokens,
          response_format: { type: "json_object" }
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
              responseMimeType: "application/json"
            }
          })
        });

        const responseData = await response.json();
        if (!response.ok) throw new Error(`Gemini API error: ${response.status}`);
        const resContent = responseData.candidates?.[0]?.content?.parts?.[0]?.text || "";
        console.log(`[AI-DIRECT] Resposta recebida (${resContent.length} chars). Preview: ${resContent.substring(0, 100)}...`);
        return resContent;
      }
    } catch (error) {
       if (i === retries - 1) throw error;
       console.warn(`[AI] Falha na tentativa ${i + 1}/${retries}. A tentar novamente em 3s...`, error instanceof Error ? error.message : error);
       await new Promise(resolve => setTimeout(resolve, 3000));
    }
  }
  return ""; 
}

function cleanJsonString(content: string): string {
  let jsonStr = content.trim();
  const codeBlockMatch = jsonStr.match(/```(?:json)?\s*([\s\S]*?)```/);
  if (codeBlockMatch && codeBlockMatch[1]) {
    return codeBlockMatch[1].trim();
  }
  const firstBrace = jsonStr.indexOf('{');
  const lastBrace = jsonStr.lastIndexOf('}');
  if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
    return jsonStr.substring(firstBrace, lastBrace + 1);
  }
  return jsonStr;
}

function sliceContext(fullText: string, currentTitle: string, nextTitle?: string): string {
  const cleanKeyword = (t: string) => t.split(':').pop()?.trim() || t;
  const startKeyword = cleanKeyword(currentTitle);
  
  let startIndex = fullText.indexOf(startKeyword);
  if (startIndex === -1) startIndex = 0;
  else startIndex = Math.max(0, startIndex - 800);

  let endIndex = fullText.length;
  if (nextTitle) {
    const endKeyword = cleanKeyword(nextTitle);
    const foundEnd = fullText.indexOf(endKeyword, startIndex + 1000);
    if (foundEnd !== -1) {
      endIndex = foundEnd + 800;
    }
  }

  console.log(`[SLICER] Otimização: Segmento de ${endIndex - startIndex} chars enviado (Módulo: ${currentTitle})`);
  return fullText.substring(startIndex, endIndex);
}

function scrubHallucinations(content: string, validPages: number[]): string {
  if (!content) return content;
  const imgRegex = /\[\[IMG_P?(\d+)_(\d+)(?:_[^\]]+)?\]\]/gi;
  let scrubbed = content;
  
  if (validPages && validPages.length > 0) {
    const matches = [...content.matchAll(imgRegex)];
    for (const match of matches) {
      const pageNum = parseInt(match[1]);
      if (!validPages.includes(pageNum)) {
        console.log(`[SCRUBBER] Alucinação de página removida: ${match[0]}`);
        scrubbed = removeImageBlock(scrubbed, match[0]);
      }
    }
  }
  
  const conceptualKeywords = ["conceptual", "ilustrativo", "diagrama conceptual", "esquema ilustrativo", "comparativo conceptual", "esquema comparativo", "representação conceptual"];
  conceptualKeywords.forEach(word => {
    const keywordRegex = new RegExp(`<figure[^>]*>.*?${word}.*?<\/figure>`, 'gis');
    const divKeywordRegex = new RegExp(`<div[^>]*class="ailms-figure"[^>]*>.*?${word}.*?<\/div>`, 'gis');
    if (keywordRegex.test(scrubbed) || divKeywordRegex.test(scrubbed)) {
        console.log(`[SCRUBBER] Alucinação conceptual removida (Keyword: ${word})`);
        scrubbed = scrubbed.replace(keywordRegex, "");
        scrubbed = scrubbed.replace(divKeywordRegex, "");
    }
  });

  return scrubbed;
}

function removeImageBlock(html: string, placeholder: string): string {
  const escapedMatch = placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const figureRegex = new RegExp(`<figure[^>]*>[^<]*<img[^>]*alt="${escapedMatch}"[^>]*>.*?<\/figure>`, 'gis');
  const divRegex = new RegExp(`<div[^>]*class="ailms-figure"[^>]*>.*?${escapedMatch}.*?<\/div>`, 'gis');
  let result = html.replace(figureRegex, "");
  result = result.replace(divRegex, "");
  return result.replace(placeholder, "");
}

export async function POST(request: NextRequest) {
  try {
    const pdfModule = await import("pdf-parse");
    const { PDFParse } = pdfModule as any;
    
    if (!PDFParse) {
      return NextResponse.json({ error: "Erro interno: Classe PDFParse não encontrada." }, { status: 500 });
    }
    
    const formData = await request.formData()
    const files = formData.getAll("files") as File[]
    const configStr = formData.get("config") as string
    const pagesWithImagesStr = formData.get("pagesWithImages") as string
    const pagesWithImages = pagesWithImagesStr ? JSON.parse(pagesWithImagesStr) : []
    const realImageFolder = (formData.get("imageFolder") as string) || "";
    
    if (files.length === 0 || !configStr) {
      return NextResponse.json({ error: "Missing files or config" }, { status: 400 })
    }

    const config: GenerationConfig = JSON.parse(configStr)
    const customPrompt = formData.get("customPrompt") as string
    
    let combinedText = ""
    for (const file of files) {
      const arrayBuffer = await file.arrayBuffer()
      const buffer = Buffer.from(arrayBuffer)
      
      try {
        const parser = new PDFParse({ data: buffer });
        const result = await parser.getText();
        
        const metadataHeader = pagesWithImages.length > 0
          ? `[SISTEMA: ESTE DOCUMENTO CONTÉM IMAGENS REAIS NAS PÁGINAS: ${pagesWithImages.join(", ")}]\n[REGRA: PROIBIDO GERAR IMAGENS FORA DESTA LISTA]\n\n`
          : `[SISTEMA: ESTE DOCUMENTO É PURO TEXTO - NÃO CONTÉM IMAGENS]\n\n`;

        combinedText += `\n--- INÍCIO DO DOCUMENTO: ${file.name} ---\n${metadataHeader}${result.text}\n`;
        await parser.destroy();
      } catch (pdfError) {
        combinedText += `\n[Erro ao extrair texto de ${file.name}]\n`
      }
    }

    if (!combinedText || combinedText.trim().length < 10) {
      return NextResponse.json({ error: "O PDF parece estar vazio ou não contém texto legível." }, { status: 400 });
    }

    const isLargeCourse = combinedText.length > 25000 || config.divideInModules;
    const fileName = files[0]?.name || "documento.pdf";
    
    const promptPath = path.join(process.cwd(), "..", "Prompts", "PROMPT_GERACAO_CURSO.md");
    let basePrompt = "";
    try {
      if (fs.existsSync(promptPath)) {
        basePrompt = fs.readFileSync(promptPath, "utf-8");
      } else {
        const altPath = "/app/Prompts/PROMPT_GERACAO_CURSO.md";
        if (fs.existsSync(altPath)) {
          basePrompt = fs.readFileSync(altPath, "utf-8");
        }
      }
    } catch (fsError) {
      console.error("Error reading master prompt file:", fsError);
    }

    const envModelPro = process.env.PORTKEY_MODEL_PRO;
    const envModelFlash = process.env.PORTKEY_MODEL_FLASH;
    const envMaxTokens = process.env.PORTKEY_MAX_TOKENS;

    const modelPro = envModelPro || "gemini-1.5-pro";
    const modelFlash = envModelFlash || "gemini-1.5-flash";
    const selectedModel = config.depth === "Especialista Técnico" ? modelPro : modelFlash;
    const maxTokensLimit = parseInt(envMaxTokens || "32768");

    let courseModules = [];
    if (isLargeCourse) {
      console.log(`[PLANNER] Curso detetado como GRANDE (${combinedText.length} chars). A iniciar fase de planeamento...`);
      
      const plannerPrompt = `
        Analisa o seguinte texto extraído de um PDF e cria um plano de formação estruturado.
        
        REGRAS CRÍTICAS:
        1. Identifica se o documento já possui uma divisão clara (ex: "Dia 1", "Módulo 1", "Capítulo 1").
        2. Se existir uma divisão original, DEVES segui-la exatamente. Se o manual tem 5 dias, gera exatamente 5 módulos.
        3. Se NÃO existir divisão, divide o conteúdo em 3 a 5 módulos lógicos, sem nunca repetir temas.
        4. É PROIBIDO criar módulos de "revisão" ou "resumo" para preencher espaço. Se o conteúdo acabar, o plano acaba.
        5. Devolve APENAS um JSON válido.

        ESTRUTURA JSON ESPERADA:
        {
          "plan": [
            { "id": 1, "title": "Módulo 1: Título Original", "summary": "Resumo fiel ao que está no manual para esta parte..." },
            ...
          ]
        }

        CONTEÚDO DO DOCUMENTO (Amostra para planeamento):
        ${combinedText.substring(0, 60000)}
      `;

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

    let finalCourse: MoodleCourse | null = null;

    if (courseModules.length > 0) {
      console.log(`[ORCHESTRATOR] Modo Modular ativo. A processar ${courseModules.length} módulos sequencialmente...`);
      
      const aggregatedActivities: any[] = [];
      const aggregatedQuestionBanks: any[] = [];
      let courseMetadata: any = null;

      const targetTotalQuestions = config.numberOfQuestions + 10;
      const questionsPerModule = Math.max(2, Math.ceil(targetTotalQuestions / courseModules.length));
      console.log(`[ORCHESTRATOR] Distribuição: ${questionsPerModule} questões por módulo para atingir ~${targetTotalQuestions} total.`);

      let globalQuestionCounter = 1;

      for (const [index, module] of courseModules.entries()) {
        // VERIFICAÇÃO DE CANCELAMENTO (AbortController)
        if (request.signal.aborted) {
          console.log("[ORCHESTRATOR] Geração interrompida pelo utilizador. A parar loop...");
          break;
        }

        const moduleNum = index + 1;
        console.log(`[ORCHESTRATOR] A gerar Módulo ${moduleNum}/${courseModules.length}: ${module.title}...`);
        
        const nextModule = courseModules[index + 1];
        const relevantText = sliceContext(combinedText, module.title, nextModule?.title);

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
          5. NUMERAÇÃO DE QUESTÕES: O nome de cada questão deve seguir a sequência global. Começa na questão nº ${globalQuestionCounter}.
          6. CONTEÚDO: Gera conteúdo denso (mínimo 500 palavras por página).
          7. INTRO/OUTRO: Não geris introduções globais ou conclusões.
          8. IMAGENS (GROUND TRUTH): Estás PROIBIDO de inventar imagens. Só podes usar placeholders [[IMG_Pxx_yy]] para as seguintes páginas que REALMENTE têm imagens: [${pagesWithImages.join(", ")}].
          
          CONTEÚDO DO DOCUMENTO (PARTE RELEVANTE):
          ${relevantText}
        `;

        const moduleResponse = await callAI(modularPrompt, selectedModel, maxTokensLimit);
        try {
          const moduleData: MoodleCourse = JSON.parse(cleanJsonString(moduleResponse));
          
          if (moduleData.activities) {
            moduleData.activities.forEach(act => {
              if (act.content) {
                act.content = scrubHallucinations(act.content, pagesWithImages);
              }
            });
          }

          if (index === 0) {
            const finalImageFolder = realImageFolder || moduleData.image_folder?.replace(/[^\w-]/g, '_') || "";
            courseMetadata = {
              course_name: moduleData.course_name,
              course_shortname: moduleData.course_shortname,
              source_file: moduleData.source_file,
              course_summary: moduleData.course_summary,
              image_folder: finalImageFolder 
            };
          }

          if (moduleData.activities) {
            const modulePages = moduleData.activities.filter(act => act.type === 'page');
            modulePages.forEach((page, pIndex) => {
              const prefix = `Módulo ${moduleNum}.${pIndex + 1}:`;
              if (!page.name.includes(prefix)) {
                const cleanName = page.name.replace(/^[^:]+:/, "").trim();
                page.name = `${prefix} ${cleanName}`;
              }
            });
            aggregatedActivities.push(...modulePages);
          }
          
          if (moduleData.question_banks) {
            for (const bank of moduleData.question_banks) {
              const bankName = "Banco Global de Questões";
              const existingBank = aggregatedQuestionBanks.find(b => b.name === bankName);
              
              if (bank.questions) {
                bank.questions.forEach((q: any) => {
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
        const introPage = {
          name: "Introdução ao Curso",
          type: "page",
          content: `<div class=\"ailms-page-container\"><h1>Bem-vindo ao curso ${courseMetadata.course_name}</h1><p>${courseMetadata.course_summary}</p></div>`
        };
        const finalQuiz = {
          name: "Exame Final de Avaliação",
          type: "quiz",
          intro: `Responda a este exame de ${config.numberOfQuestions} questões para validar os seus conhecimentos e obter a certificação.`,
          questions_per_page: 5,
          time_limit: config.quizDuration * 60,
          pass_grade: 8,
          question_banks: ["Banco Global de Questões"],
          random_questions: config.numberOfQuestions
        };
        const conclusionPage = {
          name: "Encerramento e Próximos Passos",
          type: "page",
          content: "<div class=\"ailms-page-container\"><h1>Parabéns!</h1><p>Concluiu todos os módulos teóricos. Prossiga para o exame final.</p></div>"
        };
        finalCourse = {
          ...courseMetadata,
          image_folder: realImageFolder || courseMetadata.image_folder,
          activities: [introPage, ...aggregatedActivities, conclusionPage, finalQuiz],
          question_banks: aggregatedQuestionBanks
        };
      }
    }

    if (!finalCourse) {
      if (request.signal.aborted) {
        return NextResponse.json({ error: "Geração cancelada pelo utilizador" }, { status: 499 });
      }
      console.log("[ORCHESTRATOR] A usar modo Single-Shot (Padrão)...");
      const prompt = customPrompt 
        ? `${customPrompt}\n\nCONTEÚDO DO DOCUMENTO EXTRAÍDO:\n${combinedText}\n\nResponde APENAS com o JSON integral.`
        : generatePrompt(basePrompt, config, combinedText, fileName);
      const content = await callAI(prompt, selectedModel, maxTokensLimit);
      finalCourse = JSON.parse(cleanJsonString(content));
      if (finalCourse && finalCourse.activities && pagesWithImages.length > 0) {
        finalCourse.activities.forEach(act => {
          if (act.content) act.content = scrubHallucinations(act.content, pagesWithImages);
        });
      }
      if (realImageFolder && finalCourse) finalCourse.image_folder = realImageFolder;
    }

    if (!finalCourse) throw new Error("Falha na geração");
    return NextResponse.json({ course: finalCourse });
  } catch (error) {
    console.error("Critical error:", error);
    return NextResponse.json({ error: error instanceof Error ? error.message : "Internal Server Error" }, { status: 500 });
  }
}
