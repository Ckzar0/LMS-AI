import type { GenerationConfig } from "./types"

/**
 * Realiza a substituição de variáveis dinâmicas num prompt base.
 * @param basePrompt O conteúdo do ficheiro PROMPT_GERACAO_CURSO.md
 * @param config Configurações selecionadas pelo utilizador
 * @param pdfContent Texto extraído do PDF
 * @param fileName Nome do ficheiro original
 */
export function generatePrompt(
  basePrompt: string, 
  config: GenerationConfig, 
  pdfContent: string, 
  fileName: string
): string {
  const bankSize = config.numberOfQuestions + 10;

  // Substituições de variáveis dinâmicas do prompt master
  let prompt = basePrompt
    .replace(/{{DURATION}}/g, config.depth)
    .replace(/{{DIFFICULTY}}/g, config.difficulty)
    .replace(/{{NUM_QUESTIONS}}/g, config.numberOfQuestions.toString())
    .replace(/{{BANK_SIZE}}/g, bankSize.toString())
    .replace(/{{QUIZ_DURATION}}/g, config.quizDuration.toString())
    .replace(/{{QUIZ_DURATION_SECONDS}}/g, (config.quizDuration * 60).toString())
    // Fallbacks para placeholders que possam estar no template de forma diferente
    .replace(/\${config.courseName}/g, config.courseName)
    .replace(/\${fileName}/g, fileName)
    .replace(/\${bankSize}/g, bankSize.toString())
    .replace(/\${config.depth}/g, config.depth)
    .replace(/\${config.difficulty}/g, config.difficulty)
    .replace(/\${config.numberOfQuestions}/g, config.numberOfQuestions.toString())
    .replace(/\${config.quizDuration \* 60}/g, (config.quizDuration * 60).toString());

  // Injeção de restrições negativas baseadas nas opções do utilizador
  let finalInstructions = "";
  
  if (!config.generateQuizzes || config.numberOfQuestions === 0) {
    finalInstructions += "\n- 🚫 **SEM QUIZ:** Estás PROIBIDO de gerar qualquer banco de questões (`question_banks`) ou atividade do tipo `quiz`. O curso deve ser apenas informativo.";
  }

  // Adicionar o conteúdo do PDF no final, seguindo o padrão esperado pela API
  return `${prompt}${finalInstructions}\n\nCONTEÚDO DO DOCUMENTO EXTRAÍDO:\n${pdfContent}\n\nResponde APENAS com o JSON integral.`;
}
