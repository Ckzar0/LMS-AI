import { NextRequest, NextResponse } from "next/server"
import fs from "fs"
import path from "path"

export async function GET() {
  try {
    // Try to find the prompt file in the moodle-stable directory
    // Adjust path based on where the Next.js process is running relative to the project root
    const promptPath = path.join(process.cwd(), "..", "moodle-stable", "moodle", "public", "local", "wsmanageactivities", "PROMPT_GERACAO_CURSO.md")
    
    if (fs.existsSync(promptPath)) {
      const content = fs.readFileSync(promptPath, "utf-8")
      return NextResponse.json({ content })
    } else {
      // Fallback if not found (maybe for production or different environment)
      return NextResponse.json({ error: "Prompt file not found at " + promptPath }, { status: 404 })
    }
  } catch (error) {
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 })
  }
}
