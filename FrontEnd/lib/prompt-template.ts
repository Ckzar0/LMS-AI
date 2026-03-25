import type { GenerationConfig } from "./types"

export function generatePrompt(config: GenerationConfig, pdfContent: string, fileName: string): string {
  // Tradução da profundidade para texto de instrução (conforme o seu upload.php no Moodle)
  const depthText = (config.depth === 'flash') 
    ? "RESUMO EXECUTIVO (1-2h). Foco apenas nos pontos chave, linguagem direta, eliminando detalhes secundários." : 
    (config.depth === 'deep') 
    ? "DEEP DIVE EXAUSTIVO (8-10h). Não resumas NADA. Expande cada tópico com explicações minuciosas e secções de aprofundamento." : 
    "ESTRUTURA STANDARD (3-4h). Equilibrado, preservando todo o detalhe técnico sem expansão excessiva.";
                    
  const diffText = (config.difficulty === 'easy') 
    ? "FÁCIL. Perguntas diretas de verificação de leitura básica." : 
    (config.difficulty === 'hard') 
    ? "DIFÍCIL. Perguntas complexas baseadas em análise de cenários e pensamento crítico." : 
    "MÉDIA. Aplicação prática dos conceitos.";

  return `###################################################
# ORDENS DE PRODUÇÃO DINÂMICAS:
# PROFUNDIDADE: ${depthText}
# DIFICULDADE QUIZ: ${diffText}
###################################################

Analisa COMPLETAMENTE este documento (PDF/PPT) e transforma TODO o conteúdo num curso Moodle em formato JSON usando o DESIGN GLOBAL do plugin.

# ⚠️ REGRAS CRÍTICAS DO SISTEMA (v8.9 - INTEGRAL + DESIGN GLOBAL)

## 0. IDENTIFICAÇÃO E CONFIGURAÇÃO
- Nome do curso: ${config.courseName}
- Campo source_file: Deve ser exatamente "${fileName}" (Obrigatório para automação de imagens)

## 1. APROVEITAMENTO TOTAL DO CONTEÚDO
- ✅ LER E USAR **TODAS** as páginas/slides do documento.
- ✅ NÃO omitir nenhum tópico, subtópico ou conceito.
- ✅ NÃO resumir excessivamente - preservar detalhes importantes.
- ✅ **ENGAJAMENTO:** No final de cada página longa, insere uma secção ailms-quick-check com uma pergunta de reflexão.
- ✅ **PÁGINA FINAL:** Cria sempre uma página de "Resumo e Glossário" antes do Quiz.

## 2. IMAGENS VS TABELAS (ESTRATÉGIA DE RIGOR TOTAL)
- 📊 **TABELAS COMPLEXAS:** Se uma tabela no PDF tiver muitos dados, NÃO a convertas para HTML. Usa [[TABLE_Pxx]] (onde xx é o número da página).
- 🖼️ **IMAGENS REAIS:** Associa cada imagem à página do PDF usando [[IMG_Pxx_yy]]. 
- ⚠️ **SEQUÊNCIA (yy):** O valor yy começa em 00. Se houver mais de uma imagem na página, a segunda é 01.
- 🖼️ **LEGENDA:** Imediatamente após o placeholder, escreve uma legenda: [[IMG_P15_00]] Figura: Descrição.

## 3. ESTRATÉGIA DE AVALIAÇÃO
- 📊 **UM ÚNICO QUIZ FINAL:** Não cries quizzes entre módulos.
- 📊 **BANCO DE QUESTÕES:** Cria um Banco Global com exatamente ${config.numberOfQuestions} perguntas (Multichoice, True/False e Matching).
- 📊 **REGRAS:** Passing score: 7.0, Max attempts: 3.
- 📊 **SORTEIO:** Define o campo "count" do quiz para 10.

## 4. DESIGN GLOBAL (CLASSES CSS OBRIGATÓRIAS)
Usa rigorosamente estas classes no HTML do campo "content":
- <div class="ailms-page-container"> (Container principal)
- <div class="ailms-info-box"> (Destaques e conceitos)
- <div class="ailms-img-container"> (Para imagens e legendas)
- <div class="ailms-dica"> (Conselhos práticos)
- <div class="ailms-atencao"> (Pontos críticos)

---

# FORMATO JSON REQUERIDO

{
  "course_name": "${config.courseName}",
  "course_shortname": "AI_COURSE_${Date.now()}",
  "source_file": "documento.pdf",
  "course_summary": "Resumo exaustivo...",
  
  "question_banks": [
    {
      "name": "Banco AI - ${config.courseName}",
      "questions": [
        /* CRIAR EXATAMENTE ${config.numberOfQuestions} QUESTÕES VARIADAS */
      ]
    }
  ],
  
  "activities": [
    {
      "type": "page",
      "name": "🎯 Introdução",
      "content": "<div class=\\"ailms-page-container\\">...</div>"
    },
    {
      "type": "quiz",
      "name": "🏆 Avaliação Final Abrangente",
      "intro": "Avaliação para certificação.",
      "grade": 10.0,
      "passing_score": 7.0,
      "max_attempts": 3,
      "questions_from_bank": {
        "bank_name": "Banco AI - ${config.courseName}",
        "count": 10
      }
    }
  ]
}

CONTEÚDO DO DOCUMENTO EXTRAÍDO:
${pdfContent}

Responde APENAS com o JSON integral.`
}
