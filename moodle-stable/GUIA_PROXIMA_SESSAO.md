# 🚩 Guia de Continuidade - Sessão 24/03/2026

## 📍 Ponto de Situação Atual
- **Organização:** O projeto foi centralizado na pasta `~/Desktop/LMS-AI/` (FrontEnd + Moodle Stable).
- **Moodle:** Operacional no porto 8080 (Docker) com a base de dados restaurada e permissões REST ativadas.
- **Frontend:** Operacional no porto 3000 em modo **Produção** (para evitar pânicos de cache do Turbopack).
- **IA:** O sistema foi configurado para usar o **OpenRouter** com o modelo `meta-llama/llama-3.1-8b-instruct:free` para contornar a sobrecarga do Gemini. O código do Gemini permanece comentado na rota da API para uso futuro.

## 🚧 Trabalho em Curso
1.  **Validação do OpenRouter:** Testar se o Llama 3.1 gera o JSON do curso corretamente sem erros 503.
2.  **Timeout:** Os limites de tempo das APIs (`generate-course` e `send-to-moodle`) foram aumentados para 300 segundos.
3.  **Configuração Git:** Foi criado um `.gitignore` otimizado e um `SETUP.md` completo para migração para o GitLab.

## 🔍 Próximos Passos Prioritários
- [ ] Testar a geração de curso com o novo modelo OpenRouter.
- [ ] Implementar o botão **"Copiar Prompt"** na UI de upload para permitir curadoria manual.
- [ ] Implementar a aba **"Fazer Upload de JSON"** como alternativa à geração por IA.
- [ ] Integrar dados reais do Moodle (total de cursos/alunos) no Dashboard.

## 🚀 Notas Técnicas
- O servidor Next.js deve ser iniciado com `npm run build && npm start` devido a problemas de cache do Turbopack com links simbólicos.
- As chaves de API estão no `.env.local` (não versionado).
