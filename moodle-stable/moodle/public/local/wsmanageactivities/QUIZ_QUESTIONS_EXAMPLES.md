# 🧠 Exemplos de Questões de Quiz - API wsmanageactivities

Este guia contém os formatos JSON suportados pelo motor de importação (`QuestionCreator.php`) para o Moodle 5.1+.

---

## 📋 Regras de Design (v1.1.0)
1.  **Feedback Unificado:** O sistema prioriza o `generalfeedback` (explicado abaixo da questão). Feedbacks individuais por opção são ignorados para manter a interface limpa e evitar duplicação de ícones de correção.
2.  **Fallback Automático:** Qualquer tipo de questão não reconhecido será automaticamente convertido para `multichoice` (Escolha Múltipla).
3.  **Moodle 5.1 Ready:** As questões são criadas diretamente no Banco de Questões com status `ready`.

---

## 1. Múltipla Escolha (multichoice)
O tipo padrão. Suporta uma ou várias respostas corretas (através da `fraction`).

```json
{
  "name": "Q01 - Redes de Computadores",
  "questiontext": "Qual destes protocolos opera na camada de transporte?",
  "qtype": "multichoice",
  "mark": 2.0,
  "generalfeedback": "TCP e UDP são os protocolos principais da camada de transporte.",
  "config": {
    "answers": [
      {"text": "TCP", "fraction": 1.0},
      {"text": "HTTP", "fraction": 0.0},
      {"text": "IP", "fraction": 0.0},
      {"text": "Ethernet", "fraction": 0.0}
    ]
  }
}
```

---

## 2. Verdadeiro / Falso (truefalse)
Simples e direto para verificação de conceitos.

```json
{
  "name": "Q02 - Hardware",
  "questiontext": "A memória RAM é um tipo de armazenamento volátil.",
  "qtype": "truefalse",
  "generalfeedback": "A RAM perde os dados quando a energia é cortada, por isso é volátil.",
  "config": {
    "correctanswer": true
  }
}
```

---

## 3. Correspondência (match)
Ideal para associar termos, definições ou componentes.

```json
{
  "name": "Q03 - Componentes PC",
  "questiontext": "Associe cada componente à sua função principal:",
  "qtype": "match",
  "generalfeedback": "A CPU processa, a RAM armazena temporariamente e o SSD permanentemente.",
  "config": {
    "subquestions": [
      {"text": "Processador (CPU)", "answer": "Processamento de dados"},
      {"text": "Memória RAM", "answer": "Armazenamento volátil"},
      {"text": "Disco SSD", "answer": "Armazenamento persistente"}
    ]
  }
}
```

---

## 4. Resposta Curta (shortanswer)
Requer que o aluno escreva a resposta exata.

```json
{
  "name": "Q04 - Siglas",
  "questiontext": "O que significa a sigla HTML?",
  "qtype": "shortanswer",
  "generalfeedback": "HTML significa HyperText Markup Language.",
  "config": {
    "answers": [
      {"text": "HyperText Markup Language", "fraction": 1.0}
    ]
  }
}
```

---

## 🛠️ Testar via CURL (Exemplo Granular)

Para adicionar uma questão diretamente a um quiz existente:

```bash
curl -s "http://localhost:8080/webservice/rest/server.php" \
  -d "wstoken=14c68ff68a1a57cdc4cf4d72f443b87d" \
  -d "wsfunction=local_wsmanageactivities_add_quiz_questions" \
  -d "quizid=15" \
  -d "questions[0][qtype]=truefalse" \
  -d "questions[0][name]=Questão de Teste" \
  -d "questions[0][questiontext]=O Moodle 5.1 é estável?" \
  -d "questions[0][config][correctanswer]=true" \
  -d "moodlewsrestformat=json"
```

---
**Actualizado em: 14/05/2026**
