# 🌐 Log de Experiência: Arquitetura Headless (Next.js + Moodle)

Este documento regista os avanços, decisões técnicas e problemas encontrados durante a implementação do frontend em Next.js utilizando o Moodle Stable como backend (Headless).

---

## 🕒 [25/03/2026] - Sessão de Estabilização e Novas Funcionalidades (AI Prompt & JSON)

### 🚀 Novas Funcionalidades: UI Avançada e Dashboard Real
- **Cópia de Prompt:** Implementado botão "Copiar Prompt" que extrai texto do PDF e gera o prompt completo sem enviar para a IA.
- **Upload Direto JSON:** Adicionada aba para importar cursos via JSON (ex: `test_course.json`), ignorando a IA.
- **Dashboard Dinâmico:** Integrada API `/api/dashboard-stats` para buscar total de cursos e utilizadores reais no Moodle via Web Service.

### 🔧 Correção de Infraestrutura: Conectividade Robusta
- **Fix DB Connection:** Resolvido erro "Database connection failed" através de reinício completo e restauração limpa da BD.
- **Fix Web Services:** Forçada ativação de `enablewebservices` e inserção manual das funções necessárias (`core_course_get_courses`, `core_user_get_users`, etc.) no Serviço ID 3 via SQL.
- **Fix Imagens Headless:** Alterado o caminho de salvamento de assets em `image_processor.php` para `/public/course_assets/`, garantindo que as imagens são servidas diretamente pelo Apache no porto 8080 para consumo no Next.js.

---

## 🕒 [23/03/2026] - Sessão de Estabilização e Sincronização

### 🚀 Melhoria: Resiliência da API Gemini
- **Problema:** Erro `400 Bad Request` na geração do curso.
- **Investigação:** O modelo `gemini-1.5-pro` estava a rejeitar o prompt.
- **Solução:** Trocado para `gemini-3.1-flash-lite-preview` que se revelou compatível com a estrutura do prompt. Adicionada lógica robusta para extrair o JSON da resposta, mesmo com caracteres extra.

### 🔧 Correção Técnica: Sincronização de Prompts
- **Problema:** O frontend Next.js usava um prompt de teste desatualizado.
- **Ação:** O conteúdo de `PROMPT_GERACAO_CURSO.md` foi lido e injetado diretamente na rota `/api/generate-course`, garantindo que o frontend usa sempre a versão mais recente e complexa do prompt.

### 🔌 Correção de Conectividade: Moodle Web Service
- **Problema:** Erro 500 ao tentar criar o curso. O endpoint `local_wsmanageactivities_create_course_with_content` não estava registado no Moodle.
- **Solução:** Forçada a reinstalação e o upgrade do plugin ao incrementar a versão no ficheiro `version.php` e executar `docker-compose exec webserver php admin/cli/upgrade.php`. Isto registou corretamente a nova função no Moodle.

### 🎨 Correção de UI: Erro de Renderização no Frontend
- **Problema:** Após as correções, a aplicação Next.js quebrou com um erro `React #31`.
- **Causa:** O componente `course-preview.tsx` não sabia como processar a nova estrutura de dados `questions_from_bank: { bank_name, count }` vinda da IA.
- **Solução:** O componente foi ajustado para interpretar e exibir corretamente este novo tipo de objeto, resolvendo o erro e permitindo a pré-visualização do curso gerado.

---

## 🕒 [18/03/2026] - Sessão Inicial de Integração

### 🚀 Nova Funcionalidade: Endpoint Unificado (Moodle Side)
- **Objetivo:** Permitir que o Next.js crie um curso completo (estrutura + conteúdo + avaliações) com um único pedido POST.
- **Ação:** Criada a classe `local_wsmanageactivities\external\create_course_with_content`.
- **Detalhes:** 
    - Processa JSON complexo enviado pelo Next.js.
    - Cria o curso automaticamente.
    - Cria categorias de questões no contexto do curso.
    - Gera páginas de conteúdo e quizzes aleatórios.
- **Endpoint:** `local_wsmanageactivities_create_course_with_content`

### 🚀 Melhoria: Migração para Google Gemini 1.5 Pro
- **Causa:** Erro 402 no DeepSeek (falta de saldo/créditos).
- **Ação:** Reescrita da rota `app/api/generate-course/route.ts` para usar a API do Gemini.
- **Configuração:** 
    - Modelo: `gemini-1.5-pro` (estável).
    - Modo: `responseMimeType: "application/json"` para garantir integridade do curso gerado.
    - Resiliência: Implementado fallback automático entre `v1` e `v1beta` da API da Google.

### 🔧 Correção Técnica: Pipeline de Extração de PDF
- **Problema:** Erro 400 no Gemini causado por tentativa de ler PDFs como texto simples no frontend.
- **Solução:** 
    - O frontend agora envia o `Blob` original do PDF.
    - O backend Next.js usa `pdf-parse` para extrair o texto de forma limpa antes de enviar à IA.
    - **Configuração Crítica:** Adicionado `serverExternalPackages: ["pdf-parse"]` no `next.config.mjs` para compatibilidade com o runtime do Node.js.

### 🔐 Segurança e Conectividade (CORS)
- **Ações na Base de Dados Moodle:**
    - Ativado globalmente: `enablewebservices` e protocolo `rest`.
    - Configurado CORS: Permitidas chamadas de `http://localhost:3000`.
    - Permissões: Adicionadas funções core (`get_site_info`, `get_courses`, `get_contents`) ao serviço "LMS AI Integration" (ID 3).

---

## 🚧 Problemas Resolvidos (Troubleshooting)
1. **ReferenceError:** `checkMoodleConnection` era chamada antes da inicialização. Resolvido com `useEffect` e `useCallback`.
2. **ESM Compatibility:** O import de `pdf-parse` falhava no Next.js. Resolvido com importação dinâmica e configuração de pacote externo.
3. **Moodle Error 403:** Web Services estavam desativados ou o serviço (ID 3) não tinha as funções atribuídas. Resolvido via SQL.

---

## 🛠️ Próximas Tarefas (Caso avance para Next.js)
- [ ] **Estatísticas Reais:** Criar função de API no Moodle para devolver o número de alunos e cursos para o Dashboard do Next.js.
- [ ] **Gestão de Imagens:** Integrar o `image_processor.php` no endpoint unificado para que as imagens extraídas do PDF apareçam no frontend Next.js.
- [ ] **Fluxo de Aluno:** Adaptar o `course-detail.tsx` para mostrar o conteúdo real das lições via Web Service.

---

## 📦 Ponto de Restauro
- **Backup Total Pré-NextJS:** `BACKUP_TOTAL_PRE_NEXTJS_18_03_2026` (Contém BD e MoodleData).
