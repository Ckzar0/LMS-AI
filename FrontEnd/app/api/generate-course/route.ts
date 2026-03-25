import { NextRequest, NextResponse } from "next/server"
import { generatePrompt } from "@/lib/prompt-template"
import type { GenerationConfig, MoodleCourse } from "@/lib/types"

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
    
    // USAR PROMPT CUSTOMIZADO SE FORNECIDO (Sincronizado com a Fábrica de Cursos)
    const prompt = customPrompt 
      ? `${customPrompt}\n\nCONTEÚDO DO DOCUMENTO EXTRAÍDO:\n${combinedText}\n\nResponde APENAS com o JSON integral.`
      : generatePrompt(config, combinedText, fileName)
    
    // Debug: Log text size
    console.log(`Extracted text size: ${combinedText.length} characters`);
    console.log(`Prompt size: ${prompt.length} characters`);

    /* 
    // --- LÓGICA GEMINI (COMENTADA PARA TESTES) ---
    const apiKey = process.env.GEMINI_API_KEY
    if (!apiKey) {
      return NextResponse.json({ error: "GEMINI_API_KEY not configured" }, { status: 500 })
    }

    const model = "gemini-1.5-flash"
    const geminiUrl = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${apiKey}`

    const safePrompt = prompt.length > 300000 ? prompt.substring(0, 300000) + "... [Truncated]" : prompt;

    const response = await fetch(geminiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        contents: [{ parts: [{ text: safePrompt }] }],
        generationConfig: {
          temperature: 0.8,
          maxOutputTokens: 16384,
          responseMimeType: "application/json"
        }
      })
    })

    const responseData = await response.json()

    if (!response.ok) {
      console.error("--- GEMINI API ERROR (DIAGNOSTIC) ---");
      console.error(JSON.stringify(responseData, null, 2));
      return NextResponse.json({ error: `Gemini API error: ${response.status}`, message: responseData.error?.message }, { status: response.status })
    }

    const content = responseData.candidates?.[0]?.content?.parts?.[0]?.text
    // --------------------------------------------
    */

    // --- LÓGICA OPENROUTER (TESTES) ---
    const openRouterKey = process.env.OPENROUTER_API_KEY
    if (!openRouterKey) {
      return NextResponse.json({ error: "OPENROUTER_API_KEY not configured" }, { status: 500 })
    }

    const model = "meta-llama/llama-3.1-8b-instruct:free"
    console.log(`Calling OpenRouter with model: ${model}`);


    const response = await fetch("https://openrouter.ai/api/v1/chat/completions", {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${openRouterKey}`,
        "HTTP-Referer": "http://localhost:3000", // Opcional para OpenRouter
        "X-Title": "LMS AI Integration", // Opcional para OpenRouter
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        model: model,
        messages: [{ role: "user", content: prompt }],
        temperature: 0.7,
        max_tokens: 16384
      })
    })

    const responseData = await response.json()

    if (!response.ok) {
      console.error("--- OPENROUTER API ERROR ---");
      console.error(JSON.stringify(responseData, null, 2));
      return NextResponse.json({ error: `OpenRouter API error: ${response.status}`, details: responseData }, { status: response.status })
    }

    const content = responseData.choices?.[0]?.message?.content
    // ----------------------------------

    if (!content) return NextResponse.json({ error: "No content received" }, { status: 500 })

    // Limpeza robusta do JSON
    let jsonStr = content.trim();
    if (jsonStr.includes("```")) {
      // Tentar extrair o conteúdo entre blocos de código
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
