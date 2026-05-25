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
  
  // 1. Tentar parse direto
  try { JSON.parse(trimmed); return trimmed; } catch (e) {}

  // 2. Extrair blocos de markdown
  const codeBlockMatch = trimmed.match(/```(?:json)?\s*([\s\S]*?)```/i);
  if (codeBlockMatch && codeBlockMatch[1]) {
    try { JSON.parse(codeBlockMatch[1].trim()); return codeBlockMatch[1].trim(); } catch (e) {}
  }

  // 3. Brute force: encontrar o objeto JSON mais longo válido (Objeto ou Array)
  const firstBrace = trimmed.indexOf('{');
  const firstBracket = trimmed.indexOf('[');
  
  let startIndex = -1;
  if (firstBrace !== -1 && firstBracket !== -1) {
      startIndex = Math.min(firstBrace, firstBracket);
  } else {
      startIndex = Math.max(firstBrace, firstBracket);
  }

  if (startIndex !== -1) {
    const isArray = trimmed[startIndex] === '[';
    const endChar = isArray ? ']' : '}';
    const lastIndex = trimmed.lastIndexOf(endChar);
    
    for (let i = lastIndex; i > startIndex; i--) {
      if (trimmed[i] === endChar) {
        const candidate = trimmed.substring(startIndex, i + 1);
        try { JSON.parse(candidate); return candidate; } catch (e) {}
      }
    }
  }
  return trimmed;
}

function sliceContext(fullText: string, currentTitle: string, nextTitle?: string): string {
  const cleanKeyword = (t: string) => {
    if (!t) return "";
    return t.split(':').pop()?.trim() || t;
  };
  const startKeyword = cleanKeyword(currentTitle);
  let startIndex = fullText.indexOf(startKeyword);
  if (startIndex === -1) startIndex = 0;
  else startIndex = Math.max(0, startIndex - 800);
  let endIndex = fullText.length;
  if (nextTitle) {
    const endKeyword = cleanKeyword(nextTitle);
    const foundEnd = fullText.indexOf(endKeyword, startIndex + 1000);
    if (foundEnd !== -1) endIndex = foundEnd + 800;
  }
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
        scrubbed = removeImageBlock(scrubbed, match[0]);
      }
    }
  }
  const conceptualKeywords = ["conceptual", "ilustrativo", "diagrama conceptual", "esquema ilustrativo", "comparativo conceptual", "esquema comparativo", "representação conceptual"];
  conceptualKeywords.forEach(word => {
    const keywordRegex = new RegExp(`<figure[^>]*>.*?${word}.*?<\/figure>`, 'gis');
    const divKeywordRegex = new RegExp(`<div[^>]*class="ailms-figure"[^>]*>.*?${word}.*?<\/div>`, 'gis');
    if (keywordRegex.test(scrubbed) || divKeywordRegex.test(scrubbed)) {
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
  const encoder = new TextEncoder();
  const stream = new ReadableStream({
    async start(controller) {
      const sendUpdate = (status: string, progress: number, message: string, data?: any) => {
        try { controller.enqueue(encoder.encode(JSON.stringify({ status, progress, message, ...data }) + "\n")); } catch (e) {}
      };

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
          sendUpdate("error", 0, "Missing files or config");
          return;
        }

        const config: GenerationConfig = JSON.parse(configStr);
        const customPrompt = formData.get("customPrompt") as string;

        // --- ESTADO GLOBAL ---
        let combinedText = "";
        let courseModules = [];
        const aggregatedActivities: any[] = [];
        const aggregatedQuestionBanks: any[] = [];
        let courseMetadata: any = null;
        let globalQuestionCounter = 1;
        let finalCourse: MoodleCourse | null = null;

        sendUpdate("generating", 10, "A ler documentos PDF...");
        
        for (const file of files) {
          const arrayBuffer = await file.arrayBuffer();
          const buffer = Buffer.from(arrayBuffer);
          try {
            const parser = new PDFParse({ data: buffer });
            const result = await parser.getText();
            const metadataHeader = pagesWithImages.length > 0
              ? `[SISTEMA: ESTE DOCUMENTO CONTÉM IMAGENS REAIS NAS PÁGINAS: ${pagesWithImages.join(", ")}]\n[REGRA: PROIBIDO GERAR IMAGENS FORA DESTA LISTA]\n\n`
              : `[SISTEMA: ESTE DOCUMENTO É PURO TEXTO - NÃO CONTÉM IMAGENS]\n\n`;
            combinedText += `\n--- INÍCIO DO DOCUMENTO: ${file.name} ---\n${metadataHeader}${result.text}\n`;
            await parser.destroy();
          } catch (e) {}
        }

        const isLargeCourse = combinedText.length > 25000 || config.divideInModules;
        const fileName = files[0]?.name || "documento.pdf";
        const promptPath = path.join(process.cwd(), "..", "Prompts", "PROMPT_GERACAO_CURSO.md");
        let basePrompt = "";
        if (fs.existsSync(promptPath)) basePrompt = fs.readFileSync(promptPath, "utf-8");

        const envModelPro = process.env.PORTKEY_MODEL_PRO || "@gemini-3-prod/gemini-3-pro-preview";
        const envModelFlash = process.env.PORTKEY_MODEL_FLASH || "@gemini-3-prod/gemini-3-flash-preview";
        const selectedModel = config.depth === "Especialista Técnico" ? envModelPro : envModelFlash;
        const maxTokensLimit = parseInt(process.env.PORTKEY_MAX_TOKENS || "32768");

        if (isLargeCourse) {
          sendUpdate("generating", 45, "A planear estrutura modular...");
          const plannerPrompt = `
            Analisa o conteúdo e cria um plano de formação estruturado em módulos.
            Respeita a divisão original do manual (ex: Dia 1, Módulo 1).
            Responde APENAS com um JSON no formato: { "plan": [{ "title": "...", "summary": "..." }] }
            No máximo 5 módulos.
            CONTEÚDO: ${combinedText.substring(0, 60000)}
          `;

          const plannerResponse = await callAI(plannerPrompt, envModelFlash, maxTokensLimit);
          try {
            const planData = JSON.parse(cleanJsonString(plannerResponse));
            const rawPlan = planData.plan || planData.modules || [];
            courseModules = rawPlan.map((m: any) => ({
              title: m.title || m.titulo || m.name || m.nome || "Módulo",
              summary: m.summary || m.resumo || ""
            }));
          } catch (e) { 
            courseModules = []; 
          }
        }

        if (courseModules.length > 0) {
          const questionsPerModule = Math.max(2, Math.ceil((config.numberOfQuestions + 10) / courseModules.length));

          for (const [index, module] of courseModules.entries()) {
            if (request.signal.aborted) break;
            const moduleNum = index + 1;
            const progress = 50 + Math.floor((index / courseModules.length) * 45);
            sendUpdate("generating", progress, `A gerar Módulo ${moduleNum} de ${courseModules.length}: ${module.title}...`);
            
            const nextModule = courseModules[index + 1];
            const relevantText = sliceContext(combinedText, module.title, nextModule?.title);
            
            const modularPrompt = `
              ${basePrompt}
              
              ⚠️ ESTÁS EM MODO MODULAR (PARTE ${moduleNum}/${courseModules.length}).
              FOCA-TE APENAS NO CONTEÚDO ABAIXO.
              
              REGRAS ESTRITAS PARA ESTA CHAMADA:
              1. ESTRUTURA HTML (CRÍTICO): Deves embrulhar o conteúdo em <div class="ailms-page-container">. Usa OBRIGATORIAMENTE os seguintes elementos CSS definidos no teu Master Prompt:
                 - <div class="ailms-info-box"> para Conceitos-Chave.
                 - <div class="ailms-dica"> para Dicas Práticas.
                 - <div class="ailms-atencao"> para Pontos Críticos.
                 - <div class="ailms-quick-check"> para Verificações Rápidas.
              2. NOMEAÇÃO: Todas as atividades (pages) DEVEM começar por "Módulo ${moduleNum}.X: [Título]".
              3. QUIZ: Estás PROIBIDO de criar atividades do tipo "quiz" neste JSON.
              4. QUESTÕES: Cria exatamente ${questionsPerModule} questões de avaliação.
              5. NUMERAÇÃO DE QUESTÕES: O nome de cada questão deve seguir a sequência global. Começa na questão nº ${globalQuestionCounter}.
              6. CONTEÚDO: Gera conteúdo denso (mínimo 500 palavras por página) e explica os conceitos como um Especialista Sénior (não faças apenas resumo).
              7. INTRO/OUTRO: Não geris introduções globais ou conclusões.
              8. IMAGENS E LEGENDAS: Estás PROIBIDO de inventar imagens. Só podes usar imagens das páginas reais: [${pagesWithImages.join(", ")}]. Usa OBRIGATORIAMENTE o formato com legenda exigido no Master: [[IMG_Pxx_yy_nome]] seguido da <div class="ailms-img-caption">Descrição</div>.
              9. Responde APENAS com o objeto JSON.
              
              CONTEÚDO DO DOCUMENTO (PARTE RELEVANTE):
              ${relevantText}
            `;

            const moduleResponse = await callAI(modularPrompt, selectedModel, maxTokensLimit);
            try {
              const cleaned = cleanJsonString(moduleResponse);
              let moduleData: any = JSON.parse(cleaned);
              
              // Lidar com IA que aninha os dados em chaves como "modulo" ou "course"
              if (moduleData && !moduleData.activities && moduleData.modulo) moduleData = moduleData.modulo;
              if (moduleData && !moduleData.activities && moduleData.course) moduleData = moduleData.course;

              // Lidar com IA que devolve um Array diretamente
              if (Array.isArray(moduleData)) {
                 const activities = moduleData.filter(item => item.type === 'page' || (item.name && item.content));
                 // Garantir que todos os itens identificados como atividades têm o type 'page'
                 activities.forEach(item => item.type = 'page');
                 
                 const questions = moduleData.filter(item => item.qtype || item.questiontext || (item.name && item.name.includes("Pergunta")));
                 
                 moduleData = {
                   activities: activities.length > 0 ? activities : undefined,
                   question_banks: questions.length > 0 ? [{ name: "Banco Global de Questões", questions: questions }] : undefined,
                   course_name: moduleData.find(item => item.course_name)?.course_name,
                   course_shortname: moduleData.find(item => item.course_shortname)?.course_shortname,
                   course_summary: moduleData.find(item => item.course_summary)?.course_summary,
                   image_folder: moduleData.find(item => item.image_folder)?.image_folder,
                 };
              }

              if (moduleData && moduleData.activities) {
                const modulePages = moduleData.activities.filter((act: any) => act.type === 'page');
                modulePages.forEach((page: any, pIndex: number) => {
                  if (page.content) page.content = scrubHallucinations(page.content, pagesWithImages);
                  const prefix = `Módulo ${moduleNum}.${pIndex + 1}:`;
                  if (!String(page.name || "").includes(prefix)) {
                    page.name = `${prefix} ${String(page.name || "Página").replace(/^[^:]+:/, "").trim()}`;
                  }
                });
                aggregatedActivities.push(...modulePages);
                sendUpdate("generating", progress, `Módulo ${moduleNum} processado.`);
              }

              if (moduleData.question_banks) {
                for (const bank of moduleData.question_banks) {
                  const bankName = "Banco Global de Questões";
                  const existingBank = aggregatedQuestionBanks.find(b => b.name === bankName);
                  if (bank.questions) {
                    bank.questions.forEach((q: any) => {
                      const qNumStr = globalQuestionCounter.toString().padStart(2, '0');
                      const rawQName = String(q.name || "");
                      q.name = `Pergunta ${qNumStr}: ${rawQName.split(':').pop()?.trim() || ''}`;
                      globalQuestionCounter++;
                    });
                  }
                  if (existingBank) existingBank.questions.push(...(bank.questions || []));
                  else { bank.name = bankName; aggregatedQuestionBanks.push(bank); }
                }
              }

              if (!courseMetadata && moduleData.course_name) {
                courseMetadata = { 
                  course_name: moduleData.course_name, 
                  course_shortname: moduleData.course_shortname || "CURSO", 
                  source_file: fileName, 
                  course_summary: moduleData.course_summary || "", 
                  image_folder: realImageFolder || moduleData.image_folder?.replace(/[^\w-]/g, '_') || "" 
                };
              }
            } catch (e) { 
              console.error(`Erro parse Módulo ${moduleNum}:`, e);
              // Fallback de Sobrevivência: Se a IA não der JSON nenhum, mas der HTML/Texto, aproveitamos!
              if (moduleResponse.length > 200 && !moduleResponse.startsWith("```json")) {
                  const safeContent = scrubHallucinations(moduleResponse, pagesWithImages);
                  aggregatedActivities.push({
                      type: "page",
                      name: `Módulo ${moduleNum}.1: Conteúdo de Emergência`,
                      content: `<div class="ailms-page-container"><h2>Módulo ${moduleNum}</h2>${safeContent}</div>`
                  });
                  sendUpdate("generating", progress, `Módulo ${moduleNum} processado (Modo de Recuperação).`);
              } else {
                  sendUpdate("generating", progress, `Aviso: Falha ao ler Módulo ${moduleNum}.`);
              }
            }
          }
          
          if (aggregatedActivities.length > 0) {
            const finalMetadata = courseMetadata || {
                course_name: "Curso Gerado Modular",
                course_shortname: "MOD-GEN",
                source_file: fileName,
                course_summary: "Curso modular gerado com IA.",
                image_folder: realImageFolder || ""
            };
            const activities = [
              { name: "Introdução ao Curso", type: "page", content: `<div class=\"ailms-page-container\"><h1>${finalMetadata.course_name}</h1><p>${finalMetadata.course_summary}</p></div>` },
              ...aggregatedActivities,
              { name: "Encerramento", type: "page", content: "<div class=\"ailms-page-container\"><h1>Parabéns!</h1><p>Concluiu a parte teórica.</p></div>" },
              { name: "Exame Final", type: "quiz", intro: "Avaliação final.", questions_per_page: 5, time_limit: config.quizDuration * 60, pass_grade: 8, question_banks: ["Banco Global de Questões"], random_questions: config.numberOfQuestions }
            ];
            finalCourse = { ...finalMetadata, image_folder: realImageFolder || finalMetadata.image_folder, activities, question_banks: aggregatedQuestionBanks };
            sendUpdate("generating", 95, `Finalizando curso com ${aggregatedActivities.length} atividades...`);
          }
        }

        if (!finalCourse && !request.signal.aborted && aggregatedActivities.length === 0) {
          sendUpdate("generating", 50, "A usar modo Single-Shot (Fallback)...");
          const prompt = customPrompt ? `${customPrompt}\n\nCONTEÚDO:\n${combinedText}` : generatePrompt(basePrompt, config, combinedText, fileName);
          const content = await callAI(prompt, selectedModel, maxTokensLimit);
          finalCourse = JSON.parse(cleanJsonString(content));
          if (finalCourse) {
            if (finalCourse.activities) finalCourse.activities.forEach(a => { if (a.content) a.content = scrubHallucinations(a.content, pagesWithImages); });
            finalCourse.image_folder = realImageFolder || finalCourse.image_folder;
          }
        }

        if (finalCourse) sendUpdate("complete", 100, "Geração concluída!", { course: finalCourse });
        else sendUpdate("error", 0, "Falha na geração do curso.");
      } catch (error: any) {
        sendUpdate("error", 0, error instanceof Error ? error.message : "Erro desconhecido");
      } finally {
        try { controller.close(); } catch (e) {}
      }
    }
  });

  return new Response(stream, {
    headers: { 'Content-Type': 'text/event-stream', 'Cache-Control': 'no-cache', 'Connection': 'keep-alive' },
  });
}