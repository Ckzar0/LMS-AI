# 🚩 Guia de Continuidade - AI LMS Moodle

## 📍 Ponto de Situação Atual (16/03/2026)
- **Versão:** 1.5.2 (Estável com Automações de Fluxo)
- **Backup Recente:** `backup_moodle_v3_estavel_16_03_2026.sql` (Contém todas as correções de hoje)

## 🚧 Trabalho em Curso: Banco de Questões e Categorias
Estamos a meio do processo de estabilização do Banco de Questões para o Moodle 5.x.

### 🛠️ O que foi alterado nesta sessão:
1.  **Refatoração do QuestionCreator:** As questões já não tentam gravar o `contextid` diretamente na tabela `m_question` (campo removido no M5). Agora o contexto é passado via objeto de formulário para o método `save_question`.
2.  **Vínculo de Versão:** Reforçada a atualização da tabela `m_question_versions` para o estado 'ready' e a correção manual da `m_question_bank_entries` para garantir visibilidade imediata.
3.  **Sorteio Aleatório:** Implementada a lógica `ORDER BY RAND()` no `ActivityCreator`. O sistema agora seleciona aleatoriamente X questões do banco (ex: 10 de 20) para o Quiz Final.
4.  **Limpeza:** Executada limpeza de categorias órfãs "Banco AI - ..." que não pertenciam a cursos ativos.

### 🔍 Próximos Passos (Pendentes):
- [ ] **Limpeza de Código (Aguardando autorização):**
    - Remover `QuestionBankManager.php` (Obsoleto).
    - Limpar função `refresh_question_bank` em `ActivityCreator.php`.
    - Limpar parâmetro `$target_bank_id` na assinatura de `create_quiz`.
- [ ] **Validar Conclusão do Quiz:** Testar com papel de "Estudante" se a nota >= 7.0 dispara o "V" verde e a conclusão do curso.
- [ ] **Deduplicação de Questões:** Implementar verificação para evitar duplicar perguntas se o JSON for enviado várias vezes.

## 🚀 Melhorias Concluídas Hoje (Resumo)
- ✅ **Imagens:** Sincronização por sequência (`yy`), extração híbrida (objetos + fallback inteligente) e isolamento por pasta de curso (`course_assets/{ID}/`).
- ✅ **Otimização:** Integração total do `optimize_images.py` no fluxo de upload.
- ✅ **Conclusão:** Automação da conclusão do curso por aprovação no Quiz e criação automática do Feedback.
- ✅ **Prompt:** Introdução do Protocolo de Integridade para evitar JSONs cortados.

**Instrução para Reinício:**
"Lê o LOG_CORRECOES.md e o GUIA_PROXIMA_SESSAO.md. Foca-te em validar a visibilidade das questões no banco de dados e na otimização da reutilização de categorias para evitar duuplicações."
