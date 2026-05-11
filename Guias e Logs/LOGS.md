# 📜 LOGS DE DESENVOLVIMENTO INTEGRAL - AI LMS MOODLE

## ⚠️ REGRAS DE MANUTENÇÃO (LER ANTES DE EDITAR)
1. **ORDEM CRONOLÓGICA REVERSA:** Todas as novas entradas devem ser adicionadas ao TOPO (abaixo desta regra) para facilitar a leitura das novidades.
2. **DETALHE TÉCNICO:** Apontar sempre nomes de branches, hashes de commits e ficheiros modificados.
3. **PRESERVAÇÃO:** NUNCA apagar entradas anteriores. Este ficheiro é a base para o relatório final do projeto.
4. **LOCALIZAÇÃO:** Este ficheiro deve residir em "Guias e Logs/" e ser ignorado pelo Git para evitar poluição do repositório.

---

## 📅 Maio 2026 - Consolidação de Infraestrutura e UX

### 🕒 [11/05/2026] - Reforço de Infraestrutura, Segurança e Pipeline de Imagens
- **Segurança (Hardening):** Remoção de segredos (`GEMINI_API_KEY`, `PORTKEY_API_KEY`) do `docker-compose.yml`. Implementado sistema de herança de ambiente via ficheiro `.env` local (commit `1a6f9b1`).
- **Dockerfile Customizado:** Criado `Dockerfile.moodle` para garantir a persistência de dependências críticas (`poppler-utils`, `imagemagick`, `python3-pillow`) em qualquer nova instalação.
- **Ligação LLM (Portkey):** Ativação definitiva da integração Portkey com suporte para **Config IDs** (slugs com `@`). Corrigido erro 400 ao tratar corretamente Virtual Keys e nomes de modelos.
- **Otimização de Ficheiros Grandes:** 
    - Implementado bypass de upload Base64 para PDFs >15MB. 
    - O FrontEnd agora instrui o utilizador a colocar ficheiros pesados na nova pasta raiz `/Cursos`.
    - O Moodle deteta automaticamente os ficheiros no disco, eliminando erros de memória e time-out (POST > 50MB).
- **Correção de Media no Browser:** Migração para **URLs Relativos** (`/course_assets/`) e tradução dinâmica no FrontEnd. Isto resolve definitivamente o erro de imagens que não carregam por estarem "presas" na rede interna do Docker (`http://webserver`).
- **Quick Win - UX:** Implementada a remoção automática do fórum "Announcements" na criação do curso, permitindo que o progresso do aluno atinja os 100%.
- **Sincronização:** Push e Merge realizados com sucesso para a `main` do GitHub Pessoal e branch de feature no GitLab.

### 🕒 [11/05/2026] - Estabilização de Conectividade e Motor de Imagens (Sessão Anterior)
- **Correção de Redes:** Implementada lógica de URL dinâmica no `config.php` para suportar simultaneamente chamadas internas (http://webserver) e acessos externos (http://localhost:8080).
- **Fix do Dashboard:** Resolvido o crash de `recentCourses undefined` quando a BD está limpa.
- **Motor de Imagens Docker:** Instaladas dependências `poppler-utils` e `Pillow` (Python) no contentor para garantir a extração e otimização de imagens.
- **Persistência de Prompts:** Mapeamento de volume adicionado para que o FrontEnd leia o Prompt Master em tempo real do host.

### 🕒 [11/05/2026] - Automação Total e Baseline de Infraestrutura
- **Bootstrap Automático:** Implementação do script `bootstrap.sh` que automatiza 100% do setup inicial (clonagem do core, orquestração Docker, permissões e envs).
- **Clean Baseline:** Atualização do `moodle_base_setup.sql` para um estado "limpo mas pronto", eliminando dados de teste antigos mas preservando configurações de Web Services.
- **Simplificação de Acesso:** Alteração da password padrão do admin para `admin` para facilitar a primeira entrada em novos ambientes.
- **Conectividade API:** Injeção automática do serviço 'LMS AI' e do Token fixo via SQL no processo de bootstrap, eliminando configuração manual.
- **Documentação:** Reescrita do `README.md` e `SETUP.md` com foco na portabilidade e no novo workflow de um clique.
- **Estado de Infra:** Ambiente validado como "Portátil", pronto para ser clonado e iniciado em qualquer máquina com Docker.

---

## 📅 Abril 2026 - Maturidade e Pipeline de Alta Fidelidade

### 🕒 [22/04/2026] - Correção Crítica de Histórico
- **Ação:** Identificado erro de merge que deixou a integração FE base para trás.
- **Recuperação:** Hard reset da `main` e re-sequenciamento de merges (`feat/link-fe-to-moodle` seguido de `PAI-1001`).
- **Sincronização:** Push forçado para `pessoal/main` (commit `df64408`).

### 🕒 [20/04/2026] - BUG IDENTIFICADO: Review Not Permitted
- **Problema:** Alunos não conseguem ver nota/correção após o quiz.
- **Causa:** Bitmask restritivo (69904) no `ActivityCreator.php`.
- **Planeamento:** Alterar para `458751` na próxima branch.

### 🕒 [15/04/2026] - PAI-1001: Integração Híbrida Portkey & Gemini 3.1
- **Dual-Path:** Suporte dinâmico para Portkey (Logs/Config) e Google Direct (Privacidade).
- **Output Massivo:** Suporte para janelas de até **65.536 tokens** (Gemini 3.1).
- **Dynamic Quiz Duration:** Substituição de variáveis `{{QUIZ_DURATION}}` em tempo real.
- **Densidade v10.1:** Rácio 1:1 obrigatório (1 slide = 1 página) e instrução de expansão técnica.

### 🕒 [14/04/2026] - Versão 2.0.0: Centralização e Estabilização v10.0
- **Organização:** Prompts centralizados em `/Prompts/` na raiz.
- **Sincronização:** Automatização via script `sync-prompt` no `package.json`.
- **Bug Fix (Quiz):** Suporte a múltiplos tipos de questões (matching/tf) e correção da nota mínima para "15/20 (75%)".

### 🕒 [13/04/2026] - Versão 1.9.0: Prompt v9.5 & Persona de Engenheiro
- **Persona Shift:** Alteração para "Engenheiro de Sistemas Sénior" com Few-Shot Example para combater resumos excessivos.
- **Regra 3:1:** Obrigação de gerar 3 parágrafos para cada 1 do original.
- **UI Semântica:** Novas labels: "Resumo Executivo", "Profissional", "Especialista Técnico".

### 🕒 [08/04/2026] - Versão 1.8.0: Extração via FE e Numeração Global
- **Melhoria Extração:** Implementado envio de PDF em Base64 via `/api/send-to-moodle`. Novo Web Service `process_pdf`.
- **Numeração Global (PHP):** Implementado contador estático que percorre todas as páginas (Figura 1, 2, 3...).
- **Gestão Premium:** Adicionada opção de "Apagar Permanentemente" blocos no reparador v4.9.
- **Infraestrutura:** Script `./start-moodle.sh` e atualização para PHP 8.2 (limits: 100MB upload).

---

## 📅 Março 2026 - Fundação e Estabilização Inicial

### 🕒 [23/03/2026] - Sessão de Estabilização e Sincronização
- **Resiliência API:** Trocado para `gemini-3.1-flash-lite-preview` para resolver erros 400 Bad Request.
- **Sync Prompts:** Injeção direta de `PROMPT_GERACAO_CURSO.md` na rota do Next.js.
- **Upgrade Plugin:** Correção de erros 500 ao forçar o upgrade do plugin via CLI no Docker.
- **Fix UI:** Ajuste no `course-preview.tsx` para processar a estrutura `questions_from_bank: { bank_name, count }`.

### 🕒 [18/03/2026] - Sessão Inicial de Integração (Next.js + Moodle)
- **Endpoint Unificado:** Criada a classe `create_course_with_content` no Moodle para POST único.
- **Migração Gemini 1.5 Pro:** Reescrita da rota API devido a erros 402 no DeepSeek. Implementado fallback entre `v1` e `v1beta`.
- **Pipeline PDF:** Backend Next.js usa `pdf-parse` com configuração `serverExternalPackages` para extração limpa de texto.
- **Segurança:** Configurado CORS no Moodle para permitir chamadas do porto 3000.

### 🕒 [17/03/2026] - Versão 1.5.3: Estabilização do Banco de Questões (Moodle 5.x)
- **Resolução de Contextos:** Migração da criação de questões do contexto do Curso (Level 50) para o Módulo Quiz (Level 70).
- **Compatibilidade Nativa:** Refatoração do `QuestionCreator.php` para usar a API oficial `save_question`, garantindo campos como `password` e `reviewmaxmarks`.

### 🕒 [16/03/2026] - Versão 1.5.2: Automação de Fluxo e Otimização
- **Sincronização Inteligente (yy Seq):** `image_processor.php` passa a usar o número de sequência para buscar o ficheiro exato (ex: `img-015-001.jpg`).
- **Conclusão Automática:** Implementação de critérios baseados na aprovação do quiz (tabela `course_completion_criteria`).
- **Satisfação:** Criação automática da atividade "Avaliação da Formação" via `feedback_formacao.xml`.
- **Deduplicação (Python):** Integração do `optimize_images.py`. Uso de MD5 para remover duplicados e gerar `mapping.json`.

### 🕒 [13/03/2026] - Automação Total, Prints de Tabelas e Novo Reparador
- **Extração Automática PDF:** Instalação de `poppler-utils` e `imagemagick` no Docker. Automação do motor página a página.
- **Tabelas (Estratégia):** Introdução do placeholder `[[TABLE_Pxx]]` para sinalizar locais de curadoria manual.
- **Reparador v3.1:** Adição de botão "Browse" para upload direto e previews em tempo real via JS.
- **Estabilização:** Migração para armazenamento em `/course_assets/` com URL direta, eliminando bloqueios de permissão do Moodle.

### 🕒 [10/03/2026] - Inteligência de Imagens e Banco de Questões
- **Erro 12 (Resolvido):** Questões "Invisíveis" no Banco de Dados. Reforço no `QuestionCreator.php` para forçar vínculo à categoria do curso.
- **Melhoria:** Extração automática de legendas do JSON para renomear ficheiros no Moodle (SEO & UX).
- **Melhoria:** Implementação de Busca Flexível (Fuzzy) inicial de imagens.

### 🕒 [09/03/2026] - Recuperação Total e Estabilização 5.1.3 Stable
- **Erro 10 (Resolvido):** Falha na Ligação à Base de Dados (Docker Network). Reconfiguração de portos (8080).
- **Erro 11 (Resolvido):** Erro ao Salvar Questões de Correspondência (Match) por `shuffleanswers` nulo.
- **Nuke de Sistema:** Instalação 100% limpa do Moodle 5.1.3 para resolver inconsistência de backups e contextos.

### 🕒 [04/03/2026] - Sincronização Inicial
- **Erro 1 (Resolvido):** Erro 404 ao aceder às páginas de Upload/Fix Images via `new moodle_url()`.
- **Erro 2 (Resolvido):** Questões não encontradas no Banco de Questões (Quiz Slots).
- **Erro 3 (Resolvido):** Incompatibilidade de inserção direta em `quiz_slots`. Resolvido via API de referências de questões.
- **Erro 4 (Resolvido):** Caminhos de Imagens Quebrados no Editor. Solução: Uso de `/pluginfile.php/` e criação do reparador inicial.

### 🕒 [07/05/2026] - Infraestrutura: Dockerização e Estabilização Final
- **Nova Branch:** Criada a branch `feat/containerization-setup` para isolar as mudanças de infraestrutura.
- **FrontEnd Docker:** Criação do `FrontEnd/Dockerfile` otimizado e `.dockerignore`.
- **Orquestrador Master:** Implementação do `docker-compose.yml` na raiz, unificando os 3 serviços.
- **Fix Conectividade:** Alterado `moodle-stable/moodle/config.php` para suportar `wwwroot` dinâmico, permitindo que o FrontEnd aceda à API internamente sem erros de redirecionamento.
- **Resiliência UI:** Aplicado optional chaining no `Dashboard.tsx` para evitar quebras de renderização com dados vazios.
- **Limpeza:** Remoção global de `console.log` de debug no FrontEnd para código de produção.
- **Estado Local:** Alterações confirmadas em commit local na branch `feat/containerization-setup`.
