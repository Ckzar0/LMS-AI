# 🚀 Prompt Curta V2 - PDF/PPT para Curso Moodle

## 📋 COPIAR E USAR

```
Analisa COMPLETAMENTE este documento e transforma TODO o conteúdo num curso Moodle em JSON.

⚠️ REGRAS CRÍTICAS:

1. CONTEÚDO COMPLETO
   - Usar TODAS as páginas/slides
   - NÃO omitir tópicos ou conceitos
   - Preservar detalhes técnicos e exemplos
   - Se tem 30 slides → curso deve cobrir 30 slides

2. IMAGENS OBRIGATÓRIAS
   - Incluir TODAS as imagens, diagramas, gráficos, tabelas
   - Tags: <img src="data:image/..." alt="descrição" style="max-width:100%">
   - Adicionar legendas: <p><em>Figura X: descrição</em></p>

3. QUIZZES EQUILIBRADOS (3-5 total, NÃO mais!)
   - 25% conteúdo → Quiz 1 (5 questões) validação
   - 50% conteúdo → Quiz 2 (7 questões) consolidação
   - 100% conteúdo → Quiz Final (10 questões)

ESTRUTURA:
- 1 introdução (objetivos completos)
- 1 página por cada 3-5 slides (agrupando por tema)
- 3-5 quizzes estratégicos
- 1 conclusão (resumo + próximos passos)
- 2-4 bancos de questões (10-15 questões/banco)

CADA PÁGINA HTML:
<h2>📌 Título</h2>
<p>Explicação...</p>
<h3>🔑 Conceitos-Chave</h3>
<ul><li><strong>X</strong>: explicação</li></ul>
<img src="..." alt="..." style="max-width:100%">
<p><em>Figura: legenda</em></p>
<div class="alert alert-info"><strong>💡 Dica:</strong> ...</div>

QUESTÕES:
- 50% múltipla escolha (4 opções)
- 25% verdadeiro/falso
- 25% matching
- Feedback DETALHADO (mín. 2 frases)

JSON:
{
  "course_name": "...",
  "course_shortname": "...",
  "course_summary": "...",
  "question_banks": [...],
  "activities": [
    {"type": "page", "name": "🎯 Introdução", ...},
    {"type": "page", "name": "📘 Tema 1", ...},
    {"type": "quiz", "name": "✅ Quiz Validação", ...},
    ...
  ]
}

GERA AGORA O JSON COMPLETO!
```

---

## 💡 CHECKLIST RÁPIDA

Antes de gerar, garantir:
- [ ] TODO o conteúdo PDF/PPT incluído
- [ ] TODAS as imagens relevantes
- [ ] 3-5 quizzes (não exagerar!)
- [ ] Feedback detalhado em questões
- [ ] Bancos de 10-15 questões cada

---

**Localização**: `/var/www/html/moodle2/public/local/wsmanageactivities/PROMPT_CURTA.md`
