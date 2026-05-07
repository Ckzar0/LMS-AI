# 📜 LOGS DE DESENVOLVIMENTO INTEGRAL - AI LMS MOODLE

## ⚠️ REGRAS DE MANUTENÇÃO (LER ANTES DE EDITAR)
1. **ORDEM CRONOLÓGICA:** Todas as entradas devem ser adicionadas ao FINAL do ficheiro para manter a linha do tempo.
2. **DETALHE TÉCNICO:** Apontar sempre nomes de branches, hashes de commits e ficheiros modificados.
3. **PRESERVAÇÃO:** NUNCA apagar entradas anteriores. Este ficheiro é a base para o relatório final do projeto.
4. **LOCALIZAÇÃO:** Este ficheiro deve residir em "Guias e Logs/" e ser ignorado pelo Git para evitar poluição do repositório.

---

## 📅 Março 2026 - Fundação e Estabilização Inicial

### 🕒 [04/03/2026] - Sincronização Inicial
- **Erro 1 (Resolvido):** Erro 404 ao aceder às páginas de Upload/Fix Images via `new moodle_url()`.
- **Erro 2 (Resolvido):** Questões não encontradas no Banco de Questões (Quiz Slots).
- **Erro 3 (Resolvido):** Incompatibilidade de inserção direta em `quiz_slots`. Resolvido via API de referências de questões.
- **Erro 4 (Resolvido):** Caminhos de Imagens Quebrados no Editor. Solução: Uso de `/pluginfile.php/` e criação do reparador inicial.

### 🕒 [09/03/2026] - Recuperação Total e Estabilização 5.1.3 Stable
- **Erro 10 (Resolvido):** Falha na Ligação à Base de Dados (Docker Network). Reconfiguração de portos (8080).
- **Erro 11 (Resolvido):** Erro ao Salvar Questões de Correspondência (Match) por `shuffleanswers` nulo.
- **Nuke de Sistema:** Instalação 100% limpa do Moodle 5.1.3 para resolver inconsistência de backups e contextos.

### 🕒 [10/03/2026] - Inteligência de Imagens e Banco de Questões
- **Erro 12 (Resolvido):** Questões "Invisíveis" no Banco de Dados. Reforço no `QuestionCreator.php` para forçar vínculo à categoria do curso.
- **Melhoria:** Extração automática de legendas do JSON para renomear ficheiros no Moodle (SEO & UX).
- **Melhoria:** Implementação de Busca Flexível (Fuzzy) inicial de imagens.

### 🕒 [13/03/2026] - Automação Total, Prints de Tabelas e Novo Reparador
- **Extração Automática PDF:** Instalação de `poppler-utils` e `imagemagick` no Docker. Automação do motor página a página.
- **Tabelas (Estratégia):** Introdução do placeholder `[[TABLE_Pxx]]` para sinalizar locais de curadoria manual.
- **Reparador v3.1:** Adição de botão "Browse" para upload direto e previews em tempo real via JS.
- **Estabilização:** Migração para armazenamento em `/course_assets/` com URL direta, eliminando bloqueios de permissão do Moodle.

### 🕒 [16/03/2026] - Versão 1.5.2: Automação de Fluxo e Otimização
- **Sincronização Inteligente (yy Seq):** `image_processor.php` passa a usar o número de sequência para buscar o ficheiro exato (ex: `img-015-001.jpg`).
- **Conclusão Automática:** Implementação de critérios baseados na aprovação do quiz (tabela `course_completion_criteria`).
- **Satisfação:** Criação automática da atividade "Avaliação da Formação" via `feedback_formacao.xml`.
- **Deduplicação (Python):** Integração do `optimize_images.py`. Uso de MD5 para remover duplicados e gerar `mapping.json`.

### 🕒 [17/03/2026] - Versão 1.5.3: Estabilização do Banco de Questões (Moodle 5.x)
- **Resolução de Contextos:** Migração da criação de questões do contexto do Curso (Level 50) para o Módulo Quiz (Level 70).
- **Compatibilidade Nativa:** Refatoração do `QuestionCreator.php` para usar a API oficial `save_question`, garantindo campos como `password` e `reviewmaxmarks`.

### 🕒 [18/03/2026] - Sessão Inicial de Integração (Next.js + Moodle)
- **Endpoint Unificado:** Criada a classe `create_course_with_content` no Moodle para POST único.
- **Migração Gemini 1.5 Pro:** Reescrita da rota API devido a erros 402 no DeepSeek. Implementado fallback entre `v1` e `v1beta`.
- **Pipeline PDF:** Backend Next.js usa `pdf-parse` com configuração `serverExternalPackages` para extração limpa de texto.
- **Segurança:** Configurado CORS no Moodle para permitir chamadas do porto 3000.

### 🕒 [23/03/2026] - Sessão de Estabilização e Sincronização
- **Resiliência API:** Trocado para `gemini-3.1-flash-lite-preview` para resolver erros 400 Bad Request.
- **Sync Prompts:** Injeção direta de `PROMPT_GERACAO_CURSO.md` na rota do Next.js.
- **Upgrade Plugin:** Correção de erros 500 ao forçar o upgrade do plugin via CLI no Docker.
- **Fix UI:** Ajuste no `course-preview.tsx` para processar a estrutura `questions_from_bank: { bank_name, count }`.

### 🕒 [07/05/2026] - Infraestrutura: Dockerização e Estabilização Final
- **Nova Branch:** Criada a branch `feat/containerization-setup` para isolar as mudanças de infraestrutura.
- **FrontEnd Docker:** Criação do `FrontEnd/Dockerfile` otimizado e `.dockerignore`.
- **Orquestrador Master:** Implementação do `docker-compose.yml` na raiz, unificando os 3 serviços.
- **Fix Conectividade:** Alterado `moodle-stable/moodle/config.php` para suportar `wwwroot` dinâmico, permitindo que o FrontEnd aceda à API internamente sem erros de redirecionamento.
- **Resiliência UI:** Aplicado optional chaining no `Dashboard.tsx` para evitar quebras de renderização com dados vazios.
- **Limpeza:** Remoção global de `console.log` de debug no FrontEnd para código de produção.
- **Sincronização:** Trabalho final sincronizado e enviado para GitLab (Oficial) e GitHub (Pessoal).

---

## 📅 Abril 2026 - Maturidade e Pipeline de Alta Fidelidade

### 🕒 [08/04/2026] - Versão 1.8.0: Extração via FE e Numeração Global
- **Melhoria Extração:** Implementado envio de PDF em Base64 via `/api/send-to-moodle`. Novo Web Service `process_pdf`.
- **Numeração Global (PHP):** Implementado contador estático que percorre todas as páginas (Figura 1, 2, 3...).
- **Gestão Premium:** Adicionada opção de "Apagar Permanentemente" blocos no reparador v4.9.
- **Infraestrutura:** Script `./start-moodle.sh` e atualização para PHP 8.2 (limits: 100MB upload).

### 🕒 [13/04/2026] - Versão 1.9.0: Prompt v9.5 & Persona de Engenheiro
- **Persona Shift:** Alteração para "Engenheiro de Sistemas Sénior" com Few-Shot Example para combater resumos excessivos.
- **Regra 3:1:** Obrigação de gerar 3 parágrafos para cada 1 do original.
- **UI Semântica:** Novas labels: "Resumo Executivo", "Profissional", "Especialista Técnico".

### 🕒 [14/04/2026] - Versão 2.0.0: Centralização e Estabilização v10.0
- **Organização:** Prompts centralizados em `/Prompts/` na raiz.
- **Sincronização:** Automatização via script `sync-prompt` no `package.json`.
- **Bug Fix (Quiz):** Suporte a múltiplos tipos de questões (matching/tf) e correção da nota mínima para "15/20 (75%)".

### 🕒 [15/04/2026] - PAI-1001: Integração Híbrida Portkey & Gemini 3.1
- **Dual-Path:** Suporte dinâmico para Portkey (Logs/Config) e Google Direct (Privacidade).
- **Output Massivo:** Suporte para janelas de até **65.536 tokens** (Gemini 3.1).
- **Dynamic Quiz Duration:** Substituição de variáveis `{{QUIZ_DURATION}}` em tempo real.
- **Densidade v10.1:** Rácio 1:1 obrigatório (1 slide = 1 página) e instrução de expansão técnica.

### 🕒 [20/04/2026] - BUG IDENTIFICADO: Review Not Permitted
- **Problema:** Alunos não conseguem ver nota/correção após o quiz.
- **Causa:** Bitmask restritivo (69904) no `ActivityCreator.php`.
- **Planeamento:** Alterar para `458751` na próxima branch.

### 🕒 [22/04/2026] - Correção Crítica de Histórico
- **Ação:** Identificado erro de merge que deixou a integração FE base para trás.
- **Recuperação:** Hard reset da `main` e re-sequenciamento de merges (`feat/link-fe-to-moodle` seguido de `PAI-1001`).
- **Sincronização:** Push forçado para `pessoal/main` (commit `df64408`).

---

## [2026-04-28] - Estabilização PAI-1000 e Pipeline de Imagens
**Estado:** PRODUCTION STABLE (Preview Ready)
**Branch:** `PAI-1000&image_processor`

### 🚀 Implementações e Melhorias:
1. **Pipeline de Extração de Alta Fidelidade:**
   - Comando `pdfimages -all` integrado para capturar diagramas raster.
   - Conversão PPM -> JPG via Python obrigatória no fluxo.
   - Suporte para PDFs > 50MB (limites de 100MB no Next.js e 512MB RAM no PHP).
   
2. **Smart Image Proxy & Fuzzy Match:**
   - Criado `get_image.php` com headers CORS para o FrontEnd.
   - **Fuzzy Match v2:** Se o índice exato (ex: 00) falhar, procura índices vizinhos ou qualquer imagem da página.
   - Preservação de formato: Removida a conversão forçada de zeros à esquerda que causava 404.

3. **FrontEnd Preview Sincronizado:**
   - `CoursePreview` renderiza imagens reais via Proxy Inteligente.
   - Extração de imagens agora ocorre ANTES do preview (IA e JSON).

4. **Estabilidade e Anti-Nesting:**
   - Resolvido aninhamento infinito de molduras removendo colchetes `[[ ]]` do `data-placeholder`.
   - `image_processor.php` usa Regex Lookbehind para ignorar blocos já convertidos.

### 🛠️ Correções de Ambiente:
- Recuperação de base de dados após crash do PC e corrupção do volume MariaDB.
- Permissões automáticas `chmod -R 777` em todas as extrações.
- Ativação do `mod_headers` no Apache Docker.

---

## 📂 Resumo de Ferramentas (Para Relatório Final)

| Ferramenta / Script | Descrição | Utilidade |
| :--- | :--- | :--- |
| **`optimize_images.py`** | Otimizador Python. | Conversão PPM->JPG, deduplicação MD5 e `mapping.json`. |
| **`image_processor.php`** | Motor de Conteúdo. | Conversão de placeholders, contagem global e Fuzzy Match. |
| **`get_image.php`** | Proxy de Imagens. | Serve imagens com CORS e busca inteligente para o Preview. |
| **`fix_images.php` v5.3** | Reparador Visual. | Interface estável para curadoria manual de figuras/tabelas. |
| **`ActivityCreator.php`** | Orquestrador Moodle. | Cria páginas, quizzes, conclusão e regras de disponibilidade. |

---

## 📦 Versionamento de Produto
- **Versão Atual:** 2.2.0 (Fuzzy Match Edition)
- **Maturidade:** PRODUCTION STABLE (Headless Ready)
- **Release:** `High-Fidelity Extraction + Smart Preview Sync`
### 🕒 [04/05/2026] - Branch: feat/learning-experience-ui (PAI-1012, PAI-1013, PAI-1278)
- **Sincronização:** Atualização da `main` local com o GitLab (`36f9fd6`) para garantir paridade com as melhorias de imagem.
- **LMS | FE - Cursos Recentes (PAI-1012):** Dashboard agora é totalmente clicável, permitindo navegar da lista de cursos reais para o detalhe.
- **LMS | FE - Detalhe do Curso (PAI-1013):** Implementado `course-detail.tsx` dinâmico que carrega metadados, estrutura de secções e progresso real do utilizador via API.
- **Modo de Estudo - Backend (PAI-1278):** Criada a função `local_wsmanageactivities_get_activity_content` no Moodle para extrair HTML processado (com figuras e tabelas) via Web Service.
- **Base de Dados:** Correção manual de autorização de Web Services no Moodle para permitir a nova função de extração de conteúdo.
- **Dinâmica de Cursos:** Aba "Cursos" (`courses-view.tsx`) convertida de mock para dados reais vindos do Moodle.
- **Modo de Estudo - Frontend (PAI-1278):** Implementado o componente `LearningViewer` com navegação lateral, visualização de conteúdo processado e botões de navegação sequencial.
- **Automação de Progresso:** Integração com a API do Moodle para marcar automaticamente atividades como concluídas ao serem visualizadas no FrontEnd (`mark_activity_viewed`).
- **Ponte de Dados:** Criadas rotas API no Next.js (`/api/activity/[id]` e `/api/activity/mark-viewed`) para servir de gateway entre o FE e o Moodle.
- **Quiz Engine - Suporte a Associação (PAI-1278):** Implementada a lógica para perguntas do tipo 'match' no Backend (Moodle 5.x) e UI de associação (Dropdowns) no FrontEnd.
- **UI/UX - Overhaul Visual Premium:** Estilização completa do conteúdo do curso com sistema de Cards coloridos, gradientes, sombras e animações de Fade-in.
- **Tabelas Responsivas:** Implementação de contentor com scroll horizontal e design zebra para tabelas técnicas complexas.
- **Correção de Cache:** Desativada a cache na API de estrutura do curso para garantir que o progresso (vistos) seja refletido em tempo real.
- **Escalabilidade:** A lista de cursos no FrontEnd agora exibe a totalidade dos cursos disponíveis no Moodle sem limite artificial.
- **Navegação Pós-Quiz:** Alterado o fluxo de conclusão. Ao passar no exame, o utilizador é agora direcionado para a última página do curso ("Conclusão") em vez de sair do visualizador.
- **Preparação para Certificação:** Atualizado o botão de sucesso no Quiz Engine para "Continuar para Conclusão", servindo de ponte para a futura funcionalidade de emissão de certificados.

### 🕒 [06/05/2026] - Refinação da UI, Quiz Engine & Sistema de Progresso
- **Quiz Engine (Fix & Feat):**
    - Implementado botão "Anterior" no Quiz.
    - Criado Modo de Revisão (Review) disponível após aprovação (nota >= 15).
    - Corrigido erro de baralhamento: Opções de resposta agora são enviadas em ordem aleatória pelo Moodle.
    - Implementada submissão de nota real para o Moodle Gradebook via novo Web Service `submit_quiz_grade`.
- **UI/UX Enhancements:**
    - Centrado layout de imagens e legendas no `LearningViewer`.
    - Ordenação de listas de cursos (Dashboard/Catálogo) para mostrar os mais recentes primeiro (DESC).
- **Sistema de Progresso:**
    - Substituído progresso estático de 100% por cálculo dinâmico baseado na conclusão real de atividades.
    - Implementado force-completion no Quiz para garantir visto verde imediato na sidebar.
- **Infraestrutura:**
    - Registada e autorizada manualmente a função `submit_quiz_grade` na BD do Moodle.
    - Aumentado delay de sincronização sidebar para 3.5s para acomodar persistência de dados no Moodle.

### 🕒 [06/05/2026] - Sistema de Avaliação (Feedback) & Limpeza de Upload (PAI-904, PAI-1009)
- **Avaliação da Formação (PAI-904):**
    - Criado o componente `FeedbackEngine.tsx` com sistema de estrelas (1-5) e suporte a perguntas abertas.
    - Implementada a lógica de cálculo de média automática no ecrã final de satisfação.
    - Criados Web Services no Moodle (`get_feedback_data`, `submit_feedback_responses`) para carregar perguntas do XML e gravar respostas reais na base de dados.
    - Adicionado bloqueio por nota: A avaliação agora só é desbloqueada após o aluno PASSAR no Quiz.
    - Integrado o motor de feedback no `LearningViewer.tsx` com suporte a tipos de atividade 'feedback'.
- **Extração de PDF (PAI-1009):**
    - Limpeza visual do ecrã de Upload: Removidas mensagens enganadoras de "extração local".
    - Atualizadas mensagens de progresso para refletir o processamento real no servidor e na IA.
- **Refinação UI:**
    - Adicionado toggle "Avaliação da Formação" nos Recursos Adicionais para ativação dinâmica por curso.
    - Correção de erros de runtime (TypeError) em estados de transição do componente de feedback.
