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
        { role: "system", content: "És um Especialista em Desenho de Cursos Moodle." },
        { role: "user", content: prompt }
      ],
      temperature: 0.7,
      max_tokens: maxTokens,
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
          temperature: 0.7,
          maxOutputTokens: maxTokens,
          responseMimeType: "application/json"
        }
      })
    });

    const responseData = await response.json();
    if (!response.ok) throw new Error(`Gemini API error: ${response.status}`);
    return responseData.candidates?.[0]?.content?.parts?.[0]?.text || "";
  }
}

function cleanJsonString(content: string): string {
  let jsonStr = content.trim();
  if (jsonStr.includes("```")) {
    const matches = jsonStr.match(/```(?:json)?\s*([\s\S]*?)```/);
    if (matches && matches[1]) {
      jsonStr = matches[1].trim();
    }
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
    
    // CENTRALIZAÇÃO: Ler o Master Prompt diretamente do ficheiro no Root
    const promptPath = path.join(process.cwd(), "..", "Prompts", "PROMPT_GERACAO_CURSO.md");
    let basePrompt = "";
    
    try {
      if (fs.existsSync(promptPath)) {
        basePrompt = fs.readFileSync(promptPath, "utf-8");
      } else {
        console.warn("Master prompt not found at " + promptPath + ". Using fallback logic.");
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
        ${combinedText.substring(0, 50000)} // Limite para o planner não estoirar
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

    // Gerar o prompt final usando o ficheiro mestre (ou o customPrompt se vier da Fábrica)
    const prompt = customPrompt 
      ? `${customPrompt}\n\nCONTEÚDO DO DOCUMENTO EXTRAÍDO:\n${combinedText}\n\nResponde APENAS com o JSON integral.`
      : generatePrompt(basePrompt, config, combinedText, fileName)
    
    // =========================================================================
    // CONFIGURAÇÃO DE IA: Mudar USE_PORTKEY para false para usar Gemini Direto
    // =========================================================================
    let content = "";

    if (courseModules.length > 0) {
      // TODO: Implementar o Loop da Fase 3 (Modular Orchestrator)
      // Por agora, vamos apenas gerar o primeiro módulo para teste ou manter o fluxo
      console.log("[ORCHESTRATOR] Modo Modular detetado. (A aguardar implementação da Fase 3)");
    }

    content = await callAI(prompt, selectedModel, maxTokensLimit);
    
    if (!content) {
      return NextResponse.json({ error: "No content received from AI provider" }, { status: 500 });
    }

    // Limpeza robusta do JSON
    const jsonStr = cleanJsonString(content);

    try {
      const course: MoodleCourse = JSON.parse(jsonStr)
      return NextResponse.json({ course })
    } catch (parseError) {
      console.error("Failed to parse JSON from AI. Raw content preview:", content.substring(0, 500));
      return NextResponse.json({ 
        error: "A IA gerou um JSON inválido", 
        raw: content,
        parseError: parseError instanceof Error ? parseError.message : "Unknown parse error"
      }, { status: 500 })
    }

  } catch (error) {
    console.error("Critical error:", error)
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Internal Server Error" },
      { status: 500 }
    )
  }
}
