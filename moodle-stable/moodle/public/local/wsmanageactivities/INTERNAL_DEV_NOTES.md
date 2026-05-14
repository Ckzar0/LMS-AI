# 📓 Notas de Desenvolvimento e Histórico (LMS-AI)

Este ficheiro contém informações históricas, métricas de ROI e registos de evolução técnica do plugin `wsmanageactivities`, úteis para relatórios de projeto e auditoria.

---

## 📈 Métricas de Impacto (ROI)
*   **Tempo de Criação Manual:** 45-60 minutos por curso.
*   **Tempo de Criação Automática (IA):** 3-5 minutos por curso.
*   **Eficiência:** ~92% de redução no tempo de desenvolvimento de conteúdos.
*   **Capacidade:** Passagem de um modelo artesanal para um modelo industrial (centenas de cursos/dia possíveis).

## 🛠️ Evolução da Arquitetura
1.  **Fase 1 (Granular):** WebServices isolados para cada ação (`create_page`, `create_quiz`, etc.). Causava alta latência e complexidade no FrontEnd.
2.  **Fase 2 (Unificada - Atual):** Implementação da função `create_course_with_content`. Um único POST Next.js -> Moodle orquestra toda a criação do curso, secções e atividades.

## 🐛 Fix Crítico: Transações Aninhadas (MDL-83705)
No Moodle 5.0+, foi identificada uma limitação que impedia transações de base de dados aninhadas.
*   **Problema:** Erro `call to moodle_database->start_delegated_transaction()` ao criar múltiplas atividades.
*   **Solução:** Implementação do método `clear_pending_transactions()` no `ActivityCreator.php`. Este método força o rollback de transações órfãs antes de iniciar uma nova operação de criação, garantindo estabilidade em importações massivas.

## 🔑 Legado de Tokens (Apenas para Referência Histórica)
*   *Tokens de teste antigos (XAMPP/Vagrant):*
    *   Creation: `4199cc05600eb0e28c4f6947b362aa98`
    *   Mobile: `d1fcf3a7a21bb341c2831c90abd0d334`
*   *Nota:* O sistema atual usa o token fixo `14c68ff68a1a57cdc4cf4d72f443b87d` injetado via bootstrap Docker.

---
**Documento arquivado para fins de relatório de projeto.**
