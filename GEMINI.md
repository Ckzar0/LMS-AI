> [!IMPORTANT]
> **REGRAS DE CONDUTA:** 
> 1. Nunca avances com qualquer implementação, alteração de código ou execução de tarefas complexas sem a minha autorização explícita.
> 2. **NUNCA** realizes commits de Git sem que eu peça ou autorize especificamente para cada caso.
> 3. Deves sempre apresentar a estratégia primeiro e aguardar validação.

# 🧠 Memória de Sessão - AI LMS Moodle

## 🚀 Estado Atual (Branch: feat/containerization-setup):
- **Infraestrutura & Automação:** 
    - Migração completa para ambiente 100% Docker.
    - **Bootstrap Automático:** Criado `bootstrap.sh` para setup integral num clique (Core, DB, Permissões, Env).
    - **Clean Baseline:** `moodle_base_setup.sql` atualizado com BD limpa e pré-configurada (Admin: `admin`/`admin`).
- **Conectividade:** WebServices, Tokens e Serviços API (`lms_ai`) configurados automaticamente via script.
- **Limpeza de Código:** Removidos todos os `console.log` de debug nos componentes do FrontEnd.
- **Navegação Real:** Dashboard e Lista de Cursos agora são 100% dinâmicos e ordenados pelos mais recentes (DESC).
- **Auto-Enrollment:** Inscrição automática do utilizador como estudante ao iniciar curso para garantir vistos de progresso.
- **Modo de Estudo:** `LearningViewer.tsx` com interface Premium, imagens e legendas centradas.
- **Avaliação do Curso (PAI-904):** Novo sistema de feedback com estrelas (1-5) e média automática, integrado no Moodle.
- **Quiz Engine:** 
    - Botão "Anterior" adicionado.
    - Modo de Revisão detalhado (apenas para aprovados).
    - Baralhamento (shuffle) de opções para evitar padrões fixos.
    - Submissão de nota real para o Moodle Gradebook.
- **Fluxo de Conclusão:** O sucesso no Quiz desbloqueia a Avaliação. A conclusão da Avaliação marca o fim do percurso.
- **Documentação:** Novos `README.md` e `SETUP.md` focados no workflow automatizado.

## 📂 Estrutura de Pastas:
- **Orquestração:** `docker-compose.yml` e `bootstrap.sh` (raiz).
- **Imagens Extraídas:** `moodle-stable/moodle/public/local/wsmanageactivities/extracted_images/`
- **API Moodle:** `get_activity_content.php`, `get_feedback_data.php`, `submit_feedback_responses.php`, `submit_quiz_grade.php`
- **Frontend API Gateway:** `/api/activity/`, `/api/feedback/`, `/api/quiz/grade`, `/api/course/`, `/api/dashboard-stats`

## 📌 Próximos Passos (A Iniciar):
6. **Quick Wins - UX & Progress (PAI-QW):**
    - Ocultar ou remover conclusão da secção "Announcements" (não fica verde/100%).
    - Substituir vídeo por imagem de capa nos Cards da aba "Cursos".
    - Exibir média de estrelas (Rating) nos Cards de listagem de cursos.
1. **Infraestrutura:** Push da branch `feat/containerization-setup` para GitLab/GitHub.
2. **Portkey Agnostic:** Migrar chamadas de IA para usar `PORTKEY_CONFIG_ID`, permitindo "Hot-Swap" de modelos via Dashboard.
3. **Certificação (PAI-1215):** Botão na página de conclusão para gerar PDF de certificado via Moodle (CustomCert).
4. **Áudio (TTS):** Botão "Play" para leitura do conteúdo via IA.
5. **Extração de Tabelas:** Evoluir os placeholders `[[TABLE_PXX]]` para imagens reais das tabelas do PDF.

## ⚠️ Notas Técnicas:
- O Moodle e o Next.js aceitam agora ficheiros até 100MB.
- Sempre que houver alterações na BD, atualizar o `moodle_base_setup.sql` para manter o baseline sincronizado.
- O Token de Web Service é fixo: `14c68ff68a1a57cdc4cf4d72f443b87d`.
- **Automação:** O serviço `lms_ai` e o Token são injetados automaticamente pelo `bootstrap.sh`.
