# 🚩 Guia de Continuidade - Arquitetura Headless (Next.js + Moodle)

## 📍 Ponto de Situação Atual (23/03/2026)
- **Objetivo:** Ter um frontend Next.js funcional que gera um curso via IA e o envia para o Moodle.
- **Ambiente Frontend:** `/home/cesarcabral/Desktop/FrontEnd` (Porto 3000).
- **Ambiente Backend:** Moodle Stable em `/home/cesarcabral/Desktop/moodle-stable` (Porto 8080).
- **Motor de IA:** `gemini-3.1-flash-lite-preview` (configurado e estável).

## 🚧 Trabalho Realizado na Última Sessão (23/03)
1.  **Estabilização da IA:** Corrigido o erro 400 ao trocar de modelo do Gemini e robustecer o parsing do JSON.
2.  **Sincronização de Prompt:** Garantido que o frontend usa o prompt `PROMPT_GERACAO_CURSO.md` mais recente.
3.  **Conexão Moodle-Next:** Resolvido o erro 500 ao forçar a atualização do web service do Moodle.
4.  **Correção de UI:** Resolvido o erro de renderização no React (`course-preview.tsx`) que impedia a visualização de cursos com a nova estrutura de banco de questões.

## 🔍 Próximos Passos Prioritários
- [x] **Teste de Stress de Geração:** Fazer upload de um PDF técnico real e confirmar se o Gemini gera o JSON sem erros de parsing.
- [x] **Validação do Curso no Moodle:** Após gerar, clicar em "Enviar para o Moodle" e verificar se o curso (ID devolvido) aparece corretamente no admin do Moodle.
- [x] **Implementar Pedido do Utilizador:** Adicionar no frontend as opções para "Copiar Prompt" e "Fazer Upload de JSON" como alternativas ao upload de PDF.
- [x] **Dados Reais no Dashboard:** Alterar os componentes do Next.js para buscar o total de cursos e alunos via Web Service (usar `core_course_get_courses`).
- [x] **Fix de Imagens Headless:** Adaptar o `ActivityCreator` para devolver URLs absolutas de imagens para que o Next.js as consiga renderizar.

## 🚀 Notas Técnicas para o Gemini (Próxima Sessão)
1.  Verifica sempre se o Moodle está ativo no porto 8080 antes de testar o frontend.
2.  O `pdf-parse` exige o runtime `nodejs` na rota da API do Next.js.
3.  O token de acesso é `14c68ff68a1a57cdc4cf4d72f443b87d`.

**Instrução para Reinício:**
"Lê o LOG_HEADLESS_NEXTJS.md e este guia. O nosso próximo objetivo é implementar as duas opções em falta no frontend: a capacidade de copiar o prompt gerado e a de fazer upload de um JSON de curso pré-existente."
