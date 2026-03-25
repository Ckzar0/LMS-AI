# 📚 Prompt para Gerar Curso Moodle a partir de PDF/PPT (v8.2 - INTEGRAL + DESIGN GLOBAL)

## 🎯 Objetivo
Transformar TODO o conteúdo de um PDF ou PowerPoint num curso Moodle estruturado, preservando a informação completa e aplicando o design profissional global do sistema.

---

## 📝 PROMPT COMPLETA PARA O CLAUDE / DEEPSEEK

```
Analisa COMPLETAMENTE este documento (PDF/PPT) e transforma TODO o conteúdo num curso Moodle em formato JSON usando o DESIGN GLOBAL do plugin.

# ⚠️ REGRAS CRÍTICAS

## 1. APROVEITAMENTO TOTAL DO CONTEÚDO
- ✅ LER E USAR **TODAS** as páginas/slides do documento
- ✅ NÃO omitir nenhum tópico, subtópico ou conceito
- ✅ NÃO resumir excessivamente - preservar detalhes importantes
- ✅ Se o documento tem 30 slides, o curso deve cobrir os 30 slides
- ✅ Adaptar para e-learning, mas manter profundidade e completude
- ✅ Cada 2-3 slides originais devem gerar uma página Moodle densa (mínimo 600-800 palavras)

## 2. IMAGENS - OBRIGATÓRIO
- 🖼️ **INCLUIR TODAS** as imagens, diagramas, gráficos, tabelas do documento
- 🖼️ Imagens devem ser referenciadas com <img> tags envoltas pela classe de container:
  `<div class="ailms-img-container"><img src="..." alt="..."><div class="ailms-img-caption">Legenda</div></div>`
- 🖼️ Se há screenshots, fotos, ilustrações → INCLUIR

## 3. ESTRATÉGIA DE AVALIAÇÃO (ATUALIZADO)
- 📊 **UM ÚNICO QUIZ:** O curso deve ter apenas **UM QUIZ FINAL** de avaliação no fim de todos os módulos. Não cries quizzes entre módulos.
- 📊 **BANCO DE 20 QUESTÕES:** Cria obrigatoriamente um Banco de Questões global com **20 perguntas** variadas (Multichoice, True/False e Matching).
- 📊 **REGRAS DE PASSAGEM:** No JSON do quiz, define obrigatoriamente `"passing_score": 7.0` e `"max_attempts": 3`.
- 📊 **SORTEIO ALEATÓRIO:** No JSON, define o campo "count" do quiz para 10 (isso sorteará 10 das 20 questões aleatoriamente).

---

# ESTRUTURA DO CURSO

## Fase 1: ANÁLISE DO DOCUMENTO
Primeiro, analisa o documento e responde:
1. Quantas páginas/slides tem?
2. Quais são os tópicos principais?
3. Quantas imagens/diagramas existem?
4. Qual a divisão lógica de conteúdo?

## Fase 2: PLANEAMENTO
Com base na análise, planeia:
- **Número de páginas de conteúdo**: 1 página por cada 2-3 slides (agrupando por tema)
- **Avaliação**: 1 Quiz Final exaustivo com Banco Global de 20 questões.

## Fase 3: CRIAÇÃO DO CURSO

### Páginas de Conteúdo (MODELO DESIGN GLOBAL)
**CADA página deve seguir RIGOROSAMENTE esta estrutura de classes CSS:**

```html
<div class="ailms-page-container">
    <h2>📌 [Título Principal]</h2>
    
    <p>[Introdução ao tópico - explicação clara e envolvente]</p>
    
    <div class="ailms-info-box">
        <h3>🔑 Conceitos-Chave</h3>
        <ul>
            <li><strong>Conceito 1</strong>: Explicação detalhada preservando o texto original.</li>
            <li><strong>Conceito 2</strong>: Explicação detalhada do manual.</li>
        </ul>
    </div>
    
    <h3>📊 [Subtópico Detalhado]</h3>
    <p>[Explicação exaustiva de processos, fluxos ou dados técnicos]</p>
    
    <div class="ailms-img-container">
        <img src="data:image/png;base64,..." alt="Descrição da imagem">
        <div class="ailms-img-caption">Figura X: Descrição detalhada da imagem</div>
    </div>
    
    <div class="ailms-dica">
        <strong>💡 Dica Prática:</strong> [Inserir aqui uma aplicação real ou conselho do manual]
    </div>
    
    <div class="ailms-atencao">
        <strong>⚠️ Ponto Crítico:</strong> [Alertar para cuidados ou erros comuns descritos no texto]
    </div>
    
    <table class="ailms-table">
        <thead><tr><th>Parâmetro</th><th>Descrição</th></tr></thead>
        <tbody><tr><td>Dado X</td><td>Valor Y do manual</td></tr></tbody>
    </table>
</div>
```

### Preservar detalhes técnicos:
- Fórmulas matemáticas, Passos de procedimentos, Especificações técnicas, Dados numéricos.
- Incluir TODO o texto relevante dos slides/páginas originais.

### Tipos de Questões (FORMATO CORRETO):

1. **Múltipla Escolha**:
   ```json
   {
     "name": "Título da Questão",
     "questiontext": "Pergunta completa?",
     "qtype": "multichoice",
     "answers": [
       {"text": "Opção correta", "fraction": 1, "feedback": "Explicação detalhada..."},
       {"text": "Opção errada", "fraction": 0, "feedback": "Por que está errado..."}
     ]
   }
   ```

2. **Verdadeiro/Falso**:
   ```json
   {
     "name": "Título",
     "questiontext": "Afirmação do manual?",
     "qtype": "truefalse",
     "correctanswer": true,
     "feedback": "Justificação baseada no texto."
   }
   ```

3. **Correspondência/Matching**:
   ```json
   {
     "name": "Associação",
     "questiontext": "Associe os conceitos:",
     "qtype": "matching",
     "subquestions": [
       {"text": "Conceito A", "answer": "Definição A"},
       {"text": "Conceito B", "answer": "Definição B"}
     ]
   }
   ```

---

# FORMATO JSON COMPLETO

```json
{
  "course_name": "[Título baseada no documento]",
  "course_shortname": "[CÓDIGO]",
  "course_summary": "[Descrição completa em 3-5 frases]",
  
  "question_banks": [
    {
      "name": "Banco Global de Avaliação",
      "questions": [
        /* CRIAR AQUI EXATAMENTE 20 QUESTÕES VARIADAS */
      ]
    }
  ],
  
  "activities": [
    {
      "type": "page",
      "name": "🎯 Introdução",
      "content": "<div class=\"ailms-page-container\">...</div>"
    },
    // ... inserir todas as páginas📘, 📗, 📙 necessárias ...
    {
      "type": "quiz",
      "name": "🏆 Avaliação Final Abrangente",
      "intro": "Avaliação final para certificação.",
      "grade": 10.0,
      "passing_score": 7.0,
      "max_attempts": 3,
      "questions_from_bank": {
        "bank_name": "Banco Global de Avaliação",
        "count": 10
      }
    },
    {
      "type": "page",
      "name": "🎓 Conclusão e Próximos Passos",
      "content": "<div class=\"ailms-page-container\">...</div>"
    }
  ]
}
```

---

# ✅ CHECKLIST DE QUALIDADE

## Conteúdo Completo
- [ ] TODAS as páginas/slides do documento foram usadas
- [ ] Detalhes técnicos preservados (fórmulas, dados, procedimentos)
- [ ] Exemplos do documento foram incluídos

## Design Global
- [ ] O HTML usa rigorosamente as classes ailms-page-container, ailms-info-box, etc?
- [ ] As imagens estão dentro do ailms-img-container?

## Avaliação Final
- [ ] O banco tem exatamente 20 questões variadas?
- [ ] O Quiz tem nota de passagem (7.0) e tentativas (3)?
- [ ] Feedback detalhado em TODAS as respostas?

---

# 💡 EXEMPLOS DE BOM APROVEITAMENTO

## ❌ ERRADO - Resumir demais:
Slide 1-5: "Introdução aos conceitos e vantagens"

## ✅ CORRETO - Preservar conteúdo (Design Global):
<div class="ailms-page-container">
  <h2>📌 Contexto e Fundamentos</h2>
  <p>[Texto exaustivo do slide 1]</p>
  <div class="ailms-info-box">
    <h3>🔑 Pontos Chave</h3>
    <p>[Detalhes técnicos dos slides 2 e 3]</p>
  </div>
</div>

---

**AGORA, analisa o documento anexado e gera o JSON COMPLETO seguindo RIGOROSAMENTE todas estas diretrizes!**
```
