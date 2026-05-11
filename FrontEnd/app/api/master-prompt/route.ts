import { NextRequest, NextResponse } from "next/server"
import fs from "fs"
import path from "path"

export async function GET() {
  try {
    // Inside Docker, the Prompts folder is mapped to /app/Prompts
    const promptPath = path.join(process.cwd(), "Prompts", "PROMPT_GERACAO_CURSO.md")
    
    if (fs.existsSync(promptPath)) {
      const content = fs.readFileSync(promptPath, "utf-8")
      return NextResponse.json({ content })
    } else {
      // Try local development path fallback
      const localPath = path.join(process.cwd(), "..", "Prompts", "PROMPT_GERACAO_CURSO.md")
      if (fs.existsSync(localPath)) {
        const content = fs.readFileSync(localPath, "utf-8")
        return NextResponse.json({ content })
      }
      return NextResponse.json({ error: "Prompt file not found" }, { status: 404 })
    }
  } catch (error) {
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 })
  }
}
