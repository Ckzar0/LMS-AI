> [!IMPORTANT]
> **REGRAS DE CONDUTA:** 
> 1. Nunca avances com qualquer implementação, alteração de código ou execução de tarefas complexas sem a minha autorização explícita.
> 2. **NUNCA** realizes commits de Git sem que eu peça ou autorize especificamente para cada caso.
> 3. Deves sempre apresentar a estratégia primeiro e aguardar validação.

# 🧠 Memória de Sessão - AI LMS Moodle

## 🚀 Estado Atual (Branch: feat/feedback-and-ui-cleanup):
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
- **Extração de PDF (PAI-1009):** Limpeza visual do upload para refletir o processamento real no servidor.
- **Backend:** Endpoints `get_activity_content`, \`get_feedback_data\`, \`submit_feedback_responses\` e `submit_quiz_grade` funcionais.

## 📂 Estrutura de Pastas:
- **Imagens Extraídas:** `moodle-stable/moodle/public/local/wsmanageactivities/extracted_images/`
- **Proxy de Imagens:** `http://localhost:8080/local/wsmanageactivities/get_image.php?path=[pasta]/[imagem]`
- **API Moodle:** \`get_activity_content.php\`, \`get_feedback_data.php\`, \`submit_feedback_responses.php\`, \`submit_quiz_grade.php\`
- **Feedback Engine:** `FrontEnd/components/lms/feedback-engine.tsx`
- **Quiz Engine:** `FrontEnd/components/lms/quiz-engine.tsx`
- **Frontend API Gateway:** `/api/activity/`, `/api/feedback/`, `/api/quiz/grade`, `/api/course/`, `/api/dashboard-stats`

## 📌 Próximos Passos (A Iniciar):
1. **Certificação (PAI-1215):** Botão na página de conclusão para gerar PDF de certificado via Moodle (CustomCert).
2. **Áudio (TTS):** Botão "Play" para leitura do conteúdo via IA.
3. **Extração de Tabelas:** Evoluir os placeholders `[[TABLE_PXX]]` para imagens reais das tabelas do PDF.
4. **Refinação do Fuzzy Match:** Melhorar a detecção de imagens quando a IA e o PDF têm contagens diferentes.
5. **Estatísticas Reais (PAI-999):** Integrar dados de certificados e avaliações reais na Dashboard.

## ⚠️ Notas Técnicas:
- O Moodle e o Next.js aceitam agora ficheiros até 100MB.
- Sempre que houver alterações na BD, atualizar o `moodle_base_setup.sql`.
- Versão do plugin bumpada para `2026050403` para registar `enrol_user`.
- **Autorização:** Todas as novas funções exigem associação manual ao serviço ID 3 (LMS AI) na DB ou via script PHP.
