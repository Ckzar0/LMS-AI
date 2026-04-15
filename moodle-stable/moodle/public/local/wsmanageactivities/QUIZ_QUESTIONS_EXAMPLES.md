# Exemplos de Criação de Questões - Plugin wsmanageactivities

## 1. Questão Múltipla Escolha Simples

```bash
curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${TOKEN}" \
  -d "wsfunction=local_wsmanageactivities_add_quiz_questions" \
  -d "quizid=${QUIZ_ID}" \
  -d "questions[0][type]=multichoice" \
  -d "questions[0][name]=Python Básico" \
  -d "questions[0][questiontext]=Qual é a sintaxe correta?" \
  -d "questions[0][mark]=2.0" \
  -d "questions[0][config]={\"single\":true,\"answers\":[{\"text\":\"Opção A\",\"fraction\":1.0,\"feedback\":\"Correto!\"},{\"text\":\"Opção B\",\"fraction\":0.0,\"feedback\":\"Incorreto\"}]}"
```

## 2. Questão Múltiplas Respostas Corretas

```bash
-d "questions[0][config]={\"single\":false,\"answers\":[{\"text\":\"Python\",\"fraction\":0.5},{\"text\":\"Java\",\"fraction\":0.5},{\"text\":\"HTML\",\"fraction\":0.0}]}"
```

## 3. Questão Resposta Curta

```bash
-d "questions[0][type]=shortanswer"
-d "questions[0][config]={\"case_sensitive\":false,\"answers\":[{\"text\":\"Lisboa\",\"fraction\":1.0},{\"text\":\"Lisbon\",\"fraction\":1.0}]}"
```

## 4. Questão Verdadeiro/Falso

```bash
-d "questions[0][type]=truefalse"
-d "questions[0][config]={\"correct_answer\":true,\"true_feedback\":\"Correto!\",\"false_feedback\":\"Incorreto\"}"
```

## 5. Questão Numérica

```bash
-d "questions[0][type]=numerical"
-d "questions[0][config]={\"answer\":78.54,\"tolerance\":0.5,\"answer_feedback\":\"π × r² = 78.54\"}"
```

## 6. Questão Essay

```bash
-d "questions[0][type]=essay"
-d "questions[0][config]={\"min_words\":50,\"max_words\":200}"
```

## 7. Quiz Completo com Questões

```bash
curl "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${TOKEN}" \
  -d "wsfunction=local_wsmanageactivities_create_quiz" \
  -d "courseid=${COURSE_ID}" \
  -d "sectionnum=1" \
  -d "name=Quiz Completo" \
  -d "questions[0][type]=multichoice" \
  -d "questions[0][name]=Questão 1" \
  -d "questions[0][questiontext]=Primeira questão" \
  -d "questions[0][config]={...}" \
  -d "questions[1][type]=shortanswer" \
  -d "questions[1][name]=Questão 2" \
  -d "questions[1][questiontext]=Segunda questão"
```
