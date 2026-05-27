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
            const firstSlashIndex = slug.indexOf("/");
            const vKey = slug.substring(0, firstSlashIndex);
            const mName = slug.substring(firstSlashIndex + 1);
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
        
        return chatCompletion.choices?.[0]?.message?.content || "";
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
        return responseData.candidates?.[0]?.content?.parts?.[0]?.text || "";
      }
    } catch (error: any) {
       if (i === retries - 1) throw error;
       const delay = Math.pow(2, i) * 5000;
       console.warn(`[AI] Tentativa ${i + 1} falhou. Retrying in ${delay/1000}s...`);
       await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
  return ""; 
}

function cleanJsonString(content: string): string {
  if (!content || typeof content !== 'string') return "";
  const trimmed = content.trim();
  try { JSON.parse(trimmed); return trimmed; } catch (e) {}
  const codeBlockMatch = trimmed.match(/```(?:json)?\s*([\s\S]*?)```/i);
  if (codeBlockMatch && codeBlockMatch[1]) {
    try { JSON.parse(codeBlockMatch[1].trim()); return codeBlockMatch[1].trim(); } catch (e) {}
  }
  const firstBrace = trimmed.indexOf('{');
  const firstBracket = trimmed.indexOf('[');
  const startIndex = (firstBracket !== -1 && (firstBrace === -1 || firstBracket < firstBrace)) ? firstBracket : firstBrace;
  if (startIndex !== -1) {
    const endChar = startIndex === firstBracket ? ']' : '}';
    const lastIndex = trimmed.lastIndexOf(endChar);
    if (lastIndex > startIndex) {
        for (let i = lastIndex; i > startIndex; i--) {
            if (trimmed[i] === endChar) {
                const candidate = trimmed.substring(startIndex, i + 1);
                try { JSON.parse(candidate); return candidate; } catch (e) {}
            }
        }
    }
  }
  return trimmed;
}

function sliceContext(fullText: string, currentTitle: string, nextTitle?: string, index: number = 0, total: number = 1): string {
  const cleanKeyword = (t: string) => t.split(':').pop()?.trim() || t;
  const startKeyword = cleanKeyword(currentTitle);
  let startIndex = fullText.indexOf(startKeyword);
  
  // FALLBACK PROPORCIONAL: Se a keyword não for encontrada, estima a posição pelo index
  if (startIndex === -1) {
    startIndex = Math.floor((index / total) * fullText.length);
    console.log(`⚠️ KEYWORD NOT FOUND ("${startKeyword}"). FALLBACK POSITION: ${startIndex}`);
  } else {
    startIndex = Math.max(0, startIndex - 800);
  }

  let endIndex = fullText.length;
  if (nextTitle) {
    const endKeyword = cleanKeyword(nextTitle);
    const foundEnd = fullText.indexOf(endKeyword, startIndex + 1000);
    if (foundEnd !== -1) {
        endIndex = foundEnd + 800;
    } else {
        // Fallback para o final do módulo atual
        endIndex = Math.min(fullText.length, startIndex + Math.floor(fullText.length / total) + 2000);
    }
  }
  
  return fullText.substring(startIndex, endIndex);
}

function scrubHallucinations(content: string, validPages: number[]): string {
  return content;
}

export async function POST(request: NextRequest) {
  try {
    const pdfModule = await import("pdf-parse");
    const { PDFParse } = pdfModule as any;
    
    const formData = await request.formData();
    const files = formData.getAll("files") as File[];
    const configStr = formData.get("config") as string;
    const pagesWithImagesStr = formData.get("pagesWithImages") as string;
    const pagesWithImages = pagesWithImagesStr ? JSON.parse(pagesWithImagesStr) : [];
    const realImageFolder = (formData.get("imageFolder") as string) || "";
    
    if (files.length === 0 || !configStr) {
      return NextResponse.json({ error: "Missing files or config" }, { status: 400 });
    }

    const config: GenerationConfig = JSON.parse(configStr);
    const customPrompt = formData.get("customPrompt") as string;

    const encoder = new TextEncoder();
    
    // Server-Sent Events (SSE) Stream initialization
    // This allows the server to push progress updates to the client in real-time
    // preventing browser timeouts during long AI generation processes.
    const stream = new ReadableStream({
      async start(controller) {
        const sendEvent = (event: any) => {
          try {
            controller.enqueue(encoder.encode(`data: ${JSON.stringify(event)}\n\n`));
          } catch (e) {
            console.error("Stream closed prematurely.");
          }
        };

        try {
          sendEvent({ status: "progress", message: "A estruturar conteúdo do documento PDF...", progress: 30 });
          
          let combinedText = "";
          for (const file of files) {
            const arrayBuffer = await file.arrayBuffer();
            const buffer = Buffer.from(arrayBuffer);
            try {
              const parser = new PDFParse({ data: buffer });
              const result = await parser.getText();
              combinedText += `\n--- INICIO --- ${file.name}\n${result.text}\n`;
              await parser.destroy();
            } catch (e) {}
          }

          if (!combinedText || combinedText.trim().length < 10) {
            throw new Error("O PDF não contém texto legível ou a extração falhou.");
          }

          // Injecting Ground Truth image metadata
          // This strict directive prevents the AI from hallucinating image placeholders
          // by forcing it to only use pages where physical images were extracted by Moodle.
          const systemImageInfo = `\n[SISTEMA: As seguintes páginas do PDF contêm imagens reais extraídas: ${pagesWithImages.length > 0 ? pagesWithImages.join(", ") : "Nenhuma"}]\n`;
          combinedText = systemImageInfo + combinedText;

          const isLargeCourse = combinedText.length > 15000 || config.divideInModules || config.depth === "Especialista Técnico";
          const fileName = files[0]?.name || "documento.pdf";
          const promptPath = path.join(process.cwd(), "..", "Prompts", "PROMPT_GERACAO_CURSO.md");
          let basePrompt = "";
          if (fs.existsSync(promptPath)) basePrompt = fs.readFileSync(promptPath, "utf-8");

          const envModelPro = process.env.PORTKEY_MODEL_PRO || "@gemini-3-prod/gemini-3.1-pro-preview";
          const envModelFlash = process.env.PORTKEY_MODEL_FLASH || "@gemini-3-prod/gemini-3-flash-preview";
          const selectedModel = envModelPro; 
          const maxTokensLimit = 65536;

          let finalCourse: MoodleCourse | null = null;
          let courseModules = [];

          if (isLargeCourse) {
            // Map-Reduce Phase 1: Planning
            // For large documents, we first ask a faster/cheaper model (Flash) to create a structural outline.
            sendEvent({ status: "progress", message: "A planear a estrutura pedagógica...", progress: 40 });
            const plannerPrompt = `Cria um plano de formação (3 a 5 módulos) para este conteúdo. Responde APENAS JSON: { "plan": [{ "title": "...", "summary": "..." }] }. CONTEÚDO: ${combinedText.substring(0, 80000)}`;
            const plannerRes = await callAI(plannerPrompt, envModelFlash, 4096);
            try {
              const planData = JSON.parse(cleanJsonString(plannerRes));
              courseModules = planData.plan || planData.modules || planData.plano || [];
              if (courseModules.length === 0) throw new Error("Plano vazio");
            } catch (e) {
              courseModules = [
                { title: "Parte 1: Fundamentos", summary: "Introdução ao tema." },
                { title: "Parte 2: Processos", summary: "Aprofundamento técnico." },
                { title: "Parte 3: Conclusão", summary: "Resumo e boas práticas." }
              ];
            }
          }

          if (courseModules.length > 0) {
            // Map-Reduce Phase 2: Execution Loop
            // The document is sliced proportionally and each slice is sent to the heavy model (Pro)
            // along with the specific module title. This prevents token limits and guarantees depth.
            const aggregatedActivities: any[] = [];
            const aggregatedQuestionBanks: any[] = [];
            const globalUsedImages = new Set<string>();
            let courseMetadata: any = null;
            let globalQuestionCounter = 1;
            const targetTotalQuestions = config.numberOfQuestions + 10;
            const questionsPerModule = Math.max(2, Math.ceil(targetTotalQuestions / courseModules.length));

            for (const [index, module] of courseModules.entries()) {
              const moduleNum = index + 1;
              const nextModule = courseModules[index + 1];
              
              const baseProgress = 40 + Math.floor((index / courseModules.length) * 55);
              sendEvent({ 
                status: "progress", 
                message: `A gerar Módulo ${moduleNum} de ${courseModules.length}: ${module.title}...`, 
                progress: baseProgress 
              });

              // Context Slicing: Extracting only the relevant text for the current module
              const relevantText = sliceContext(combinedText, module.title, nextModule?.title, index, courseModules.length);
              
              const modularPrompt = `
                ${basePrompt}
                
                ⚠️ ESTÁS EM MODO MODULAR (PARTE ${moduleNum}/${courseModules.length}).
                O teu objetivo é gerar APENAS o conteúdo para o módulo: "${module.title}".
                
                🖼️ LISTA DE PÁGINAS COM IMAGENS REAIS NESTE DOCUMENTO: ${pagesWithImages.length > 0 ? pagesWithImages.join(", ") : "Nenhuma imagem detectada"}
                🚫 IMAGENS JÁ UTILIZADAS EM MÓDULOS ANTERIORES: ${Array.from(globalUsedImages).join(", ") || "Nenhuma"}
                (REGRA CRÍTICA: Só podes gerar [[IMG_Pxx_yy]] se o "xx" estiver na lista de permitidas e NÃO tiver sido usada antes!)
                
                🎯 FORMATO DE RESPOSTA OBRIGATÓRIO (JSON APENAS):
                {
                  "activities": [
                    { "type": "page", "name": "Módulo ${moduleNum}.X: [Título]", "content": "HTML denso... [[IMG_Pxx_yy]] <div class=\\"ailms-img-caption\\">Figura: [Legenda]</div> ..." }
                  ],
                  "questions": [
                    { 
                      "qtype": "multichoice", 
                      "questiontext": "Enunciado da pergunta?", 
                      "answers": [
                        { "text": "Opção Correta", "fraction": 1.0 },
                        { "text": "Opção Errada", "fraction": 0.0 }
                      ] 
                    },
                    {
                      "qtype": "truefalse",
                      "questiontext": "Afirmação?",
                      "correctanswer": true
                    }
                  ]
                }
                
                OBJETIVOS PARA ESTE MÓDULO:
                1. CONTEÚDO TÉCNICO: Gera no mínimo 3 páginas exaustivas e ricas.
                2. SEMÂNTICA OBRIGATÓRIA: Cada página deve incluir pelo menos:
                   - <div class="ailms-info-box"><h3>🔑 Conceitos-Chave</h3><ul>...</ul></div>
                   - <div class="ailms-dica"><strong>💡 Dica Prática:</strong> ...</div>
                   - <div class="ailms-atencao"><strong>⚠️ Ponto Crítico:</strong> ...</div>
                   - <div class="ailms-quick-check"><strong>🤔 Verificação Rápida:</strong> [Pergunta de reflexão]</div>
                3. IMAGENS: Insere placeholders [[IMG_Pxx_yy]] APENAS se o conteúdo visual for indispensável. Coloca a legenda SEMPRE imediatamente após o placeholder usando <div class=\"ailms-img-caption\">...</div>.
                4. EXAME: Gera exatamente ${questionsPerModule} questões inéditas.
                
                CONTEÚDO RELEVANTE (Módulo ${moduleNum}):
                ${relevantText}
              `;

              let moduleResponse = "";
              try {
                moduleResponse = await callAI(modularPrompt, selectedModel, maxTokensLimit);
                
                // Error Recovery / Fallback
                // If the model fails or returns a truncated response, we retry with the Flash model
                // to guarantee the module is not skipped.
                if (moduleResponse.length < 500) {
                  sendEvent({ status: "progress", message: `Atenção: A recriar Módulo ${moduleNum} (falha da IA)...`, progress: baseProgress + 2 });
                  moduleResponse = await callAI(modularPrompt, envModelFlash, 4096);
                }

                // Data Extraction Engine
                // Attempts to salvage valid JSON or arrays even if the AI wraps them in markdown blocks or text.
                const cleaned = cleanJsonString(moduleResponse);
                let moduleData = JSON.parse(cleaned);
                
                let moduleActivities: any[] = [];
                let moduleQuestions: any[] = [];

                if (Array.isArray(moduleData)) {
                  moduleActivities = moduleData.filter(item => (item.content || item.html || item.text || item.conteudo || (item.texto && String(item.texto).length > 50)) && !item.qtype);
                  moduleQuestions = moduleData.filter(item => item.qtype || item.questiontext || item.pergunta || item.answers);
                } else if (typeof moduleData === 'object' && moduleData !== null) {
                  const rawActs = moduleData.activities || moduleData.paginas || moduleData.conteudo || moduleData.pages || moduleData.atividades || [];
                  moduleActivities = Array.isArray(rawActs) ? rawActs : [rawActs];
                  const rawQs = (moduleData.question_banks && moduleData.question_banks[0]?.questions) || moduleData.question_banks || moduleData.questions || moduleData.perguntas || moduleData.banco || [];
                  moduleQuestions = Array.isArray(rawQs) ? rawQs : [rawQs];
                  
                  if (moduleActivities.length === 0 && moduleQuestions.length === 0) {
                      for (const key of Object.keys(moduleData)) {
                          if (Array.isArray(moduleData[key]) && moduleData[key].length > 0) {
                              const first = moduleData[key][0];
                              if (first.content || first.html || (first.text && first.text.length > 200)) moduleActivities = moduleData[key];
                              if (first.qtype || first.questiontext || first.pergunta) moduleQuestions = moduleData[key];
                          }
                      }
                  }
                }

                if (moduleActivities.length > 0) {
                  moduleActivities.forEach((p: any) => {
                      const textContent = p.content || p.html || p.text || p.conteudo || p.texto || "";
                      
                      const imageMatches = textContent.match(/\[\[IMG_P?(\d+)_(\d+)(?:_[^\]]+)?\]\]/g);
                      if (imageMatches) {
                          imageMatches.forEach(img => globalUsedImages.add(img));
                      }

                      if (textContent.length < 50) return;
                      p.type = 'page';
                      p.content = textContent;
                      const prefix = `Módulo ${moduleNum}.${aggregatedActivities.filter(a => a.name.includes(`Módulo ${moduleNum}`)).length + 1}:`;
                      if (!String(p.name || p.title || p.titulo || "").includes(`Módulo ${moduleNum}`)) {
                          p.name = `${prefix} ${String(p.name || p.title || p.titulo || "Página").replace(/^[^:]+:/, "").trim()}`;
                      } else {
                          p.name = p.name || p.title || p.titulo || "Página";
                      }
                      aggregatedActivities.push(p);
                  });
                }

                if (moduleQuestions.length > 0) {
                  const bankName = "Banco Global de Questões";
                  let existingBank = aggregatedQuestionBanks.find(b => b.name === bankName);
                  if (!existingBank) {
                      existingBank = { name: bankName, questions: [] };
                      aggregatedQuestionBanks.push(existingBank);
                  }
                  moduleQuestions.forEach((q: any) => {
                    const qText = q.questiontext || q.text || q.pergunta || "";
                    if (!qText && !q.qtype) return;

                    if (q.answers && Array.isArray(q.answers)) {
                      q.answers = q.answers.map((a: any) => {
                         if (typeof a === 'string') return { text: a, fraction: 0 };
                         return { 
                           text: a.text || a.answer || a.opcao || "", 
                           fraction: a.fraction !== undefined ? parseFloat(a.fraction) : (a.correct ? 1.0 : 0.0)
                         };
                      });
                    }
                    
                    if (q.qtype === 'truefalse' || q.type === 'truefalse') {
                      q.correctanswer = q.correctanswer !== undefined ? q.correctanswer : (q.answer === 'true' || q.correct === true);
                    }

                    q.name = `Pergunta ${globalQuestionCounter++}: ${q.name || q.title || q.titulo || qText.substring(0, 30)}`;
                    existingBank.questions.push(q);
                  });
                }

                if (index === 0 && !courseMetadata) {
                  courseMetadata = {
                    course_name: (moduleData as any).course_name || config.courseName,
                    course_shortname: (moduleData as any).course_shortname || "AI-COURSE",
                    source_file: fileName,
                    course_summary: (moduleData as any).course_summary || "Gerado com IA.",
                    image_folder: realImageFolder || (moduleData as any).image_folder || ""
                  };
                }
              } catch (e) {
                if (moduleResponse && moduleResponse.length > 200) {
                  aggregatedActivities.push({
                    type: "page",
                    name: `Módulo ${moduleNum}: Recuperação de Conteúdo`,
                    content: `<div class="ailms-page-container"><h2>Módulo ${moduleNum}</h2>${moduleResponse}</div>`
                  });
                }
              }
            }

            if (aggregatedActivities.length > 0) {
              const activities = [
                { name: "📘 Introdução ao Curso", type: "page", content: `<div class="ailms-page-container"><h1>Bem-vindo</h1><p>${courseMetadata?.course_summary || ''}</p></div>` },
                ...aggregatedActivities,
                { name: "✅ Encerramento", type: "page", content: '<div class="ailms-page-container"><h1>Concluído!</h1></div>' }
              ];

              if (config.generateQuizzes) {
                  activities.push({ 
                      name: "🎯 Exame Final", type: "quiz", intro: "Avaliação final.", 
                      questions_per_page: 5, time_limit: config.quizDuration * 60, 
                      pass_grade: 15.0, question_banks: ["Banco Global de Questões"], random_questions: config.numberOfQuestions 
                  });
              }

              finalCourse = { 
                  course_name: courseMetadata?.course_name || config.courseName, 
                  course_shortname: "AI-COURSE", source_file: fileName, 
                  course_summary: "Gerado com IA.", image_folder: realImageFolder, 
                  activities, 
                  question_banks: aggregatedQuestionBanks,
                  generate_evaluation: config.generateEvaluation,
                  generate_certificate: config.generateCertificate
              };
            }
          }

          if (!finalCourse) {
            sendEvent({ status: "progress", message: "A gerar curso (Modo Standard)...", progress: 60 });
            const prompt = generatePrompt(basePrompt, config, combinedText, fileName);
            const content = await callAI(prompt, selectedModel, maxTokensLimit);
            finalCourse = JSON.parse(cleanJsonString(content));
            if (finalCourse) {
              finalCourse.generate_evaluation = config.generateEvaluation;
              finalCourse.generate_certificate = config.generateCertificate;
            }
          }

          sendEvent({ status: "complete", course: finalCourse });
          controller.close();
        } catch (err: any) {
          sendEvent({ status: "error", error: err.message });
          controller.close();
        }
      }
    });

    return new NextResponse(stream, {
      headers: {
        "Content-Type": "text/event-stream",
        "Cache-Control": "no-cache, no-transform",
        "Connection": "keep-alive",
      },
    });

  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
