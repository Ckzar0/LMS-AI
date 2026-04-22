# 📜 Histórico Integral de Logs e Correções (LMS AI Moodle)

Este documento é a compilação exaustiva de todos os registos técnicos, erros resolvidos e decisões de arquitetura do projeto. **Não remover informação deste ficheiro**, pois serve de base para relatórios técnicos.

---

## 🕒 [15/04/2026] - PAI-1001: Integração Híbrida Portkey & Gemini 3.1
- **Problema:** A rota de geração estava limitada a modelos fixos e janelas de tokens pequenas (16k), insuficiente para o modo Especialista Técnico.
- **Solução:** 
    - Implementação de lógica de modelos dinâmicos (`PORTKEY_MODEL_PRO` / `PORTKEY_MODEL_FLASH`).
    - Suporte para janelas de output de até **65.536 tokens** (Gemini 3.1).
    - Arquitetura de "Dual-Path": Suporte para Portkey (Logs/Config) e Google Direct (Privacidade/Gratuitidade).
    - Adição de logs de diagnóstico para detetar fallbacks de variáveis de ambiente.
- **Estado:** ✅ Validado e Integrado.

## 🕒 [15/04/2026] - Versão 1.10.0: Centralização Total e Dynamic Quiz Duration
### 🚀 Melhoria: Arquitetura de "Single Source of Truth" (Prompt Master)
- **Problema:** O prompt mestre existia em três locais diferentes, causando discrepâncias.
- **Solução:** Centralização absoluta em `Prompts/PROMPT_GERACAO_CURSO.md`. Refatoração da API Next.js para ler o prompt diretamente do Root. Adicionado script `sync-prompt` no `package.json`.
- **Impacto:** Garantia de consistência total entre IA Direta, Fábrica de Cursos e LLM Externo.

### 🚀 Melhoria: Resolução da Duração do Quiz (Dynamic Injection)
- **Problema:** O parâmetro de tempo do quiz definido na interface não era aplicado no Moodle (ficava sempre a 30 min).
- **Solução:** Substituição do valor fixo 1800 no prompt por variáveis dinâmicas `{{QUIZ_DURATION}}` e `{{QUIZ_DURATION_SECONDS}}`.
- **Estado:** ✅ Validado.

### 🚀 Melhoria: Reforço de Densidade "Deep Dive" (v10.1)
- **Problema:** O modo Especialista Técnico gerava cursos curtos devido à compressão do LLM.
- **Solução:** Implementação de métricas obrigatórias de 400-600 palavras/página e rácio 1:1 rigoroso (1 slide = 1 página).
- **Estado:** ✅ Implementado no Master Prompt.

### 🏛️ Nota Arquitetural: Racional SPA vs App Router
- **Decisão:** Manter navegação via `useState` (SPA) para o MVP para garantir transições de 0ms e gestão centralizada do estado efémero da IA.

---

## 🕒 [14/04/2026] - Versão 2.0.0: Centralização e Estabilização v10.0
- **Organização:** Todos os prompts movidos para `/Prompts/` na raiz.
- **Sincronização:** Moodle (`upload.php`) e FrontEnd (`prompt-template.ts`) agora usam o Prompt Master v10.0.
- **Bug Fix (Frontend):** Resolvido erro "Unterminated template" e "isGenerating is not defined".
- **Bug Fix (Moodle):** Resolvido erro de "Ficheiro de prompt não encontrado" via cópia local e volume Docker.
- **Melhoria UI:** Cores emerald removidas em favor da marca. Display de nota mínima corrigido para "15/20 (75%)".

---

## 🕒 [13/04/2026] - Versão 1.9.0: Prompt v9.5 & Persona de Engenheiro
- **Melhoria: Combate ao Resumo Excessivo (Persona Shift)**
    - Alteração da persona para **"Engenheiro de Sistemas Sénior"**.
    - Inclusão de Few-Shot Example para o Gemini imitar.
    - Regra de "Proibição de Resumo": Obrigação de gerar 3 parágrafos para cada 1 do original.
- **Melhoria: Atualização Semântica da UI**
    - Novas labels: "Resumo Executivo", "Profissional (Padrão)", "Especialista Técnico (Deep Dive)".

---

## 🕒 [08/04/2026] - Versão 1.8.0: Extração via FE e Numeração Global
- **Melhoria: Extração de Imagens via FrontEnd (Next.js)**
    - Envio do PDF em Base64 através da rota `/api/send-to-moodle`.
    - Criado Web Service `local_wsmanageactivities_process_pdf` para processar o ficheiro.
- **Melhoria: Numeração Sequencial Global (PHP-Based)**
    - Implementado contador estático em PHP que percorre todas as páginas (Figura 1, 2, 3...).
- **Melhoria: Gestão de Imagens Premium (v4.9)**
    - Adicionada opção de "Apagar Permanentemente" blocos indesejados no reparador.
- **Infraestrutura:** Criação do script `./start-moodle.sh` e atualização para PHP 8.2 (limits: 100MB upload / 512MB RAM).

---

## 📅 Março 2026 - Fase de Automação Headless

### 🕒 [25/03/2026] - Sessão de Estabilização e Fábrica de Cursos
- **Fábrica de Cursos:** Implementada aba de configuração manual que permite ajustar Profundidade/Dificuldade e copiar o prompt master integral.
- **Sincronização Total:** O prompt da geração automática é agora idêntico ao da Fábrica.
- **Prompt Preview:** Adicionado Accordion na UI para visualizar o texto antes do envio à IA.
- **Dashboard Dinâmico:** Integração real com `/api/dashboard-stats` (cursos e utilizadores reais).

### 🕒 [23/03/2026] - Sessão de Estabilização e Sincronização
- **Resiliência Gemini:** Trocado para `gemini-3.1-flash-lite-preview` para resolver erros 400.
- **Conectividade:** Forçada ativação de Web Services via SQL.
- **Fix Imagens Headless:** Redirecionamento de assets para `/public/course_assets/` (acessíveis no porto 8080).
- **Fix UI:** Ajuste no `course-preview.tsx` para processar a estrutura `questions_from_bank`.

### 🕒 [18/03/2026] - Sessão Inicial de Integração (Next.js + Moodle)
- **Endpoint Unificado:** Criada a classe `create_course_with_content` no Moodle para POST único.
- **Migração Gemini 1.5 Pro:** Reescrita da rota API para usar Google Gemini (v1 e v1beta).
- **Pipeline PDF:** Frontend envia Blob, Backend Next.js usa `pdf-parse` (runtime `nodejs` e `serverExternalPackages`).
- **Segurança:** Configurado CORS para `http://localhost:3000` no Moodle.

---

## 📅 Março 2026 - Automação Moodle Core

### 🕒 [17/03/2026] - Versão 1.5.3: Estabilização do Banco de Questões
- **Solução Contextos:** Migração da criação de questões para o contexto do Módulo Quiz (Level 70).
- **Solução API:** Uso da API oficial `save_question` para garantir campos obrigatórios (password, reviewmaxmarks).

### 🕒 [16/03/2026] - Versão 1.5.2: Automação e Otimização
- **Sincronização de Imagens:** `image_processor.php` agora usa o número de sequência (yy) do placeholder para busca exata (fuzzy p-1/p+1).
- **Conclusão Automática:** Lógica no `ActivityCreator` para conclusão baseada na aprovação do quiz.
- **Avaliação (Feedback):** Criação automática da atividade baseada em `feedback_formacao.xml`.
- **Deduplicação (Python):** `optimize_images.py` usa MD5 para remover logótipos repetidos.

### 🕒 [13/03/2026] - Automação Total, Prints de Tabelas e Novo Reparador
- **Extração Automática:** Instalação de `poppler-utils` no contentor. Extração página a página automática no `upload.php`.
- **Tabelas (Estratégia):** Placeholder `[[TABLE_Pxx]]` para sinalizar curadoria manual em infográficos.
- **Reparador v3.1:** Botão "Carregar imagem" (Browse) para upload direto e previews em tempo real.

---

## 📅 Março 2026 - Fundação e Recuperação (Moodle 5.x)

### 🕒 [10/03/2026] - Inteligência de Imagens e Banco de Questões
- **Erro 12 (Resolvido):** Questões invisíveis vinculadas manualmente à categoria do curso.
- **Melhoria:** Extração automática de legendas do JSON para renomear ficheiros no Moodle.

### 🕒 [09/03/2026] - Recuperação Total 5.1.3 Stable
- **Erro 10 (Resolvido):** Falha na ligação BD via Docker Network (Porto 8080).
- **Erro 11 (Resolvido):** Coluna `shuffleanswers` nula em questões Match.
- **Nuke:** Instalação limpa do Moodle 5.1.3 para resolver inconsistência de backups.

### 🕒 [04/03/2026] - Sincronização Inicial
- **Erro 1 (Resolvido):** 404 via `new moodle_url()`.
- **Erro 3 (Resolvido):** Incompatibilidade de inserção direta em `quiz_slots`.

---

## 📂 Ferramentas e Scripts Criados (Resumo para Relatório)

| Ferramenta / Script | Descrição | Utilidade |
| :--- | :--- | :--- |
| **`optimize_images.py`** | Script Python de pós-processamento. | Converte imagens para JPG, remove duplicadas (MD5) e gera o `mapping.json`. |
| **`image_processor.php`** | Motor de processamento de conteúdo. | Converte placeholders em HTML, gere nomes descritivos e resolve buscas "fuzzy". |
| **`fix_images.php` v3.1** | Ferramenta de reparação administrativa. | Interface para troca manual de imagens com preview em tempo real e upload via Browse. |
| **`QuestionCreator.php`** | Importador de perguntas Moodle 5.x. | Traduz o JSON para a API do Moodle, garantindo contexto de curso correto. |
| **`ActivityCreator.php`** | Criador de estrutura de curso. | Gere a criação de páginas, quizzes e navegação automática. |

---

## 📦 Versionamento de Produto
- **Versão:** 2.1.0 (PAI-1001)
- **Release:** `Hybrid LLM Gateway + Gemini 3.1 Support`
- **Maturidade:** PRODUCTION STABLE (Headless Ready)
- **Estado:** Implementado e validado via Portkey/Google Direct.

---
*Fim do log exaustivo.*
