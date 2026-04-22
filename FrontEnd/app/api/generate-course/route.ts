import { NextRequest, NextResponse } from "next/server"
import { generatePrompt } from "@/lib/prompt-template"
import type { GenerationConfig, MoodleCourse } from "@/lib/types"
import fs from "fs"
import path from "path"

export const runtime = "nodejs"
export const maxDuration = 300 // 5 minutos

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

    // Gerar o prompt final usando o ficheiro mestre (ou o customPrompt se vier da Fábrica)
    const prompt = customPrompt 
      ? `${customPrompt}\n\nCONTEÚDO DO DOCUMENTO EXTRAÍDO:\n${combinedText}\n\nResponde APENAS com o JSON integral.`
      : generatePrompt(basePrompt, config, combinedText, fileName)
    
    // Debug: Log text size
    console.log(`Extracted text size: ${combinedText.length} characters`);
    console.log(`Prompt size: ${prompt.length} characters`);

    // --- ESCOLHA DO MODELO BASEADA NA PROFUNDIDADE ---
    const envModelPro = process.env.PORTKEY_MODEL_PRO;
    const envModelFlash = process.env.PORTKEY_MODEL_FLASH;
    const envMaxTokens = process.env.PORTKEY_MAX_TOKENS;

    const modelPro = envModelPro || "gemini-1.5-pro";
    const modelFlash = envModelFlash || "gemini-1.5-flash";
    const selectedModel = config.depth === "Especialista Técnico" ? modelPro : modelFlash;
    const maxTokensLimit = parseInt(envMaxTokens || "32768");

    // Log de Diagnóstico de Configuração
    if (!envModelPro || !envModelFlash || !envMaxTokens) {
      console.warn("⚠️ [CONFIG] Algumas variáveis de modelo não foram encontradas no .env.local. Usando fallbacks de segurança.");
    }
    console.log(`[LLM] Selecionado: ${selectedModel} (Fonte: ${config.depth === "Especialista Técnico" ? (envModelPro ? ".env" : "Fallback") : (envModelFlash ? ".env" : "Fallback")})`);
    console.log(`[LLM] Max Tokens: ${maxTokensLimit} (Fonte: ${envMaxTokens ? ".env" : "Fallback"})`);

    /* 
    // =========================================================================
    // OPÇÃO A: PORTKEY GATEWAY (COMENTADO - USAR PARA LOGS/MODELOS ESPECIAIS)
    // =========================================================================
    const portkeyKey = process.env.PORTKEY_API_KEY
    const virtualKey = process.env.PORTKEY_VIRTUAL_KEY
    
    if (!portkeyKey) {
      return NextResponse.json({ error: "PORTKEY_API_KEY not configured" }, { status: 500 })
    }

    const { default: Portkey } = await import("portkey-ai");
    const portkeyConfig: any = { apiKey: portkeyKey };
    let finalModel = selectedModel;

    if (selectedModel.startsWith("@")) {
      const parts = selectedModel.split("/");
      portkeyConfig.virtualKey = parts[0].substring(1);
      finalModel = parts.slice(1).join("/");
    } else if (virtualKey) {
      portkeyConfig.virtualKey = virtualKey;
    } else {
      portkeyConfig.provider = "google";
    }

    const portkey = new Portkey(portkeyConfig);
    const chatCompletion = await portkey.chat.completions.create({
      model: finalModel,
      messages: [
        { role: "system", content: "És um Especialista em Desenho de Cursos Moodle." },
        { role: "user", content: prompt }
      ],
      temperature: 0.7,
      max_tokens: maxTokensLimit,
    });
    const content = chatCompletion.choices?.[0]?.message?.content;
    */

    // =========================================================================
    // OPÇÃO B: GOOGLE GEMINI DIRETO (ATIVO - SEM CUSTOS PORTKEY / PRIVACIDADE)
    // =========================================================================
    const geminiKey = process.env.GEMINI_API_KEY;
    if (!geminiKey) {
      return NextResponse.json({ error: "GEMINI_API_KEY not configured" }, { status: 500 });
    }

    // Limpar o nome do modelo (remover @slug se existir)
    const cleanModel = selectedModel.startsWith("@") 
      ? selectedModel.split("/").slice(1).join("/") 
      : selectedModel;

    console.log(`Calling Google Gemini Direct - Model: ${cleanModel}`);

    const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/${cleanModel}:generateContent?key=${geminiKey}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        contents: [{ parts: [{ text: prompt }] }],
        generationConfig: {
          temperature: 0.7,
          maxOutputTokens: maxTokensLimit,
          responseMimeType: "application/json"
        }
      })
    });

    const responseData = await response.json();

    if (!response.ok) {
      console.error("--- GEMINI API ERROR ---", JSON.stringify(responseData, null, 2));
      return NextResponse.json({ error: `Gemini API error: ${response.status}`, details: responseData }, { status: response.status });
    }

    // A estrutura do Gemini é candidates[0].content.parts[0].text
    const content = responseData.candidates?.[0]?.content?.parts?.[0]?.text;
    
    if (!content) {
      console.error("--- NO CONTENT FROM GEMINI ---", JSON.stringify(responseData, null, 2));
      return NextResponse.json({ error: "No content received from Gemini" }, { status: 500 });
    }

    // Limpeza robusta do JSON
    let jsonStr = content.trim();
    if (jsonStr.includes("```")) {
      const matches = jsonStr.match(/```(?:json)?\s*([\s\S]*?)```/);
      if (matches && matches[1]) {
        jsonStr = matches[1].trim();
      }
    }

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
