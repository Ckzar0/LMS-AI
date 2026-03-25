# 📋 Log de Alterações, Erros e Soluções - Moodle Stable (8080)

⚠️ AVISO CRÍTICO: NÃO APAGAR REGISTOS ANTERIORES. ESTE FICHEIRO É USADO PARA RELATÓRIOS. ADICIONAR SEMPRE NO TOPO OU NA SECÇÃO CORRESPONDENTE.

Este documento regista cronologicamente todos os problemas encontrados e as soluções aplicadas durante a manutenção e migração para o ambiente estável.

---

## 🕒 [17/03/2026] - Versão 1.5.3: Estabilização do Banco de Questões (Moodle 5.x)

### 🚀 Melhoria: Resolução Definitiva de Contextos de Questão
- **Problema:** Erro `Invalid context id specified` ao tentar aceder ou editar questões. O Moodle 5.x perdia a linhagem de contextos entre o curso e o módulo.
- **Solução:** 
    - Migração da criação de questões do contexto do Curso (Level 50) diretamente para o contexto do Módulo Quiz (Level 70).
    - Integração do processamento do banco de questões no fluxo do `ActivityCreator::create_quiz`.
    - Uso da categoria padrão do Quiz (`Default for [Quiz Name]`) para armazenar as 20 questões programáticas.
- **Estado:** ✅ Resolvido e Estável.

### 🚀 Melhoria: Compatibilidade Nativa Moodle 5.x (Quiz & Questions)
- **Problema:** Erros de escrita na base de dados por falta de campos obrigatórios (`password`, `reviewmaxmarks`).
- **Solução:** 
    - Refatoração total do `QuestionCreator.php` para usar a API oficial `save_question`.
    - Inclusão de todos os campos técnicos obrigatórios no objeto `moduleinfo` do Quiz.
- **Estado:** ✅ Implementado.

---

## 🕒 [16/03/2026] - Versão 1.5.2: Automação de Fluxo e Otimização de Imagens

### 🚀 Melhoria: Sincronização Inteligente de Imagens (yy Seq)
- **Problema:** O sistema ignorava o número de sequência do placeholder (`[[IMG_P15_01]]`), resultando em repetições de logótipos.
- **Solução:** O `image_processor.php` agora usa o `yy` para buscar o ficheiro exato (ex: `img-015-001.jpg`). Adição de busca com tolerância (fuzzy) para páginas vizinhas (`p-1`, `p+1`).
- **Estado:** ✅ Implementado.

### 🚀 Melhoria: Conclusão Automática de Curso
- **Problema:** Alunos aprovados no Quiz Final não tinham o curso marcado como concluído automaticamente.
- **Solução:** Implementação de lógica no `ActivityCreator::set_course_completion` para configurar critérios de conclusão baseados na aprovação do quiz (tabela `course_completion_criteria`).
- **Estado:** ✅ Implementado.

### 🚀 Melhoria: Automação de Inquérito de Satisfação (Feedback)
- **Problema:** Necessidade de criar manualmente a avaliação da formação em cada curso.
- **Solução:** Criação automática da atividade "Avaliação da Formação" no final do curso, importando itens de `feedback_formacao.xml`.
- **Estado:** ✅ Implementado (compatível com Moodle 5.x).

### 🚀 Melhoria: Otimização e Deduplicação de Imagens (Python)
- **Problema:** Muitos duplicados (logótipos) extraídos do PDF enchiam o servidor.
- **Solução:** 
    - Integração do `optimize_images.py` no fluxo de upload.
    - O script usa MD5 para remover duplicados e gera um `mapping.json`.
    - O `image_processor.php` consulta o mapa para redirecionar imagens apagadas para a versão original.
- **Estado:** ✅ Implementado.

### 🚀 Melhoria: Isolamento de Assets por Curso
- **Problema:** Imagens de cursos diferentes misturavam-se na pasta `course_assets`.
- **Solução:** Redirecionamento de salvamento para `/course_assets/{ID_CURSO}/`.
- **Estado:** ✅ Implementado.

### 🚀 Melhoria: Protocolo de Integridade do JSON (Prompt Master)
- **Problema:** IAs cortavam a resposta antes de incluir o Quiz Final.
- **Solução:** Atualização do `PROMPT_GERACAO_CURSO.md` com ordens explícitas de paragem para continuidade e lista de verificação de fecho de JSON.
- **Estado:** ✅ Implementado.

---

## 🕒 [13/03/2026] - Automação Total, Prints de Tabelas e Novo Reparador

### 🚀 Melhoria: Extração Automática de PDF (Docker-Ready)
- **Problema:** A extração de imagens falhava dentro do Docker por falta de ferramentas e acessos.
- **Solução:** 
    - Instalação de `poppler-utils` e `imagemagick` diretamente no contentor do Moodle.
    - Automação do motor no `upload.php` para extrair imagens **página a página** (garantindo nomes como `img-015-xxx.jpg`).
    - Redirecionamento da busca de PDFs para a pasta interna `/moodle/Cursos/`.
- **Estado:** ✅ Implementado e Automatizado.

### 🚀 Melhoria: Fix Images v3.1 (Upload Direto & Previews)
- **Problema:** Tabelas complexas eram lidas erradamente pela IA.
- **Solução:** 
    - Introdução do placeholder `[[TABLE_Pxx]]` para sinalizar locais de curadoria manual.
    - Adição de botão **"Carregar imagem"** (Browse) no reparador para upload direto do PC.
    - Implementação de previews em tempo real (JS) para facilitar a escolha.
- **Estado:** ✅ Implementado.

### 🚀 Melhoria: Estabilização do Image Processor
- **Problema:** Imagens repetidas ou invisíveis devido a falhas de contexto.
- **Solução:** Migração para armazenamento em `/course_assets/` com URL direta, eliminando bloqueios do Moodle. Implementação de busca sequencial robusta.
- **Estado:** ✅ Implementado.

---

## 🕒 [10/03/2026] - Inteligência de Imagens e Banco de Questões

### ❌ Erro 12: Questões "Invisíveis" no Banco de Dados do Curso
- **Sintoma:** As questões eram criadas mas não apareciam no banco de questões do curso para edição.
- **Causa:** No Moodle 5.x, a criação manual falhava ao vincular a `question_bank_entries` à categoria do curso.
- **Solução:** Reforço no `QuestionCreator.php` para forçar o vínculo da entrada do banco de questões ao ID da categoria do curso.
- **Estado:** ✅ Resolvido.

### 🚀 Melhoria: Nomes Descritivos de Imagens (SEO & UX)
- **Problema:** Imagens gravadas com nomes genéricos.
- **Solução:** Extração automática de legendas do JSON para renomear ficheiros no Moodle.
- **Estado:** ✅ Implementado.

### 🚀 Melhoria: Busca Flexível (Fuzzy) de Imagens
- **Estado:** ✅ Implementado.

---

## 🕒 [09/03/2026] - Recuperação Total e Estabilização 5.1.3 Stable

### ❌ Erro 10: Falha na Ligação à Base de Dados (Docker Network)
- **Sintoma:** Erro "Database connection failed".
- **Solução:** Reconfiguração de portos (8080) e restauração do backup funcional `v1`.
- **Estado:** ✅ Resolvido.

### ❌ Erro 11: Erro ao Salvar Questões de Correspondência (Match)
- **Sintoma:** Erro `Column 'shuffleanswers' cannot be null`.
- **Solução:** Implementação do suporte para questões de correspondência com `shuffleanswers=1`.
- **Estado:** ✅ Resolvido.

### ❌ Erro 6: Inconsistência de Contextos e Versões (Backup 5.2 vs Código 5.1.3)
- **Solução:** Instalação 100% limpa do Moodle 5.1.3 (Nuke).
- **Estado:** ✅ Resolvido.

### ❌ Erro 7: Falha Crítica ao Salvar Questões (Tabelas Inexistentes)
- **Causa:** Prefixo `m_` duplicado manualmente no código.
- **Solução:** Remoção de prefixos manuais das chamadas `$DB`.
- **Estado:** ✅ Resolvido.

### ❌ Erro 8: Quiz com "Tipos Inválidos" e Referências Quebradas
- **Causa:** Uso do campo `area` em vez de `questionarea`.
- **Estado:** ✅ Resolvido.

### ❌ Erro 9: Aviso de Plugin Corrompido (`backups_temp`)
- **Estado:** ✅ Resolvido.

---

## 🕒 [04/03/2026] - Sincronização e Estabilização Inicial

### ❌ Erro 1: Erro 404 ao aceder às páginas de Upload/Fix Images
- **Solução:** Uso de `new moodle_url()` em vez de caminhos estáticos.
- **Estado:** ✅ Resolvido.

### ❌ Erro 2: Questões não encontradas no Banco de Questões (Quiz Slots)
- **Estado:** ✅ Resolvido.

### ❌ Erro 3: Incompatibilidade de inserção direta em `quiz_slots`
- **Solução:** API de referências de questões.
- **Estado:** ✅ Resolvido.

### ❌ Erro 4: Caminhos de Imagens Quebrados no Editor
- **Solução:** Uso de `/pluginfile.php/` e criação do reparador.
- **Estado:** ✅ Resolvido.

---

## 📂 Ferramentas e Scripts Criados

| Ferramenta / Script | Descrição | Utilidade |
| :--- | :--- | :--- |
| **`optimize_images.py`** | Script Python de pós-processamento. | Converte imagens para JPG, remove duplicadas (MD5) e gera o `mapping.json`. |
| **`image_processor.php`** | Motor de processamento de conteúdo. | Converte placeholders em HTML, gere nomes descritivos e resolve buscas "fuzzy". |
| **`fix_images.php` v3.1** | Ferramenta de reparação administrativa. | Interface para troca manual de imagens com preview em tempo real e upload via Browse. |
| **`QuestionCreator.php`** | Importador de perguntas Moodle 5.x. | Traduz o JSON para a API do Moodle, garantindo contexto de curso correto. |
| **`ActivityCreator.php`** | Criador de estrutura de curso. | Gere a criação de páginas, quizzes e navegação automática. |

---

## 📦 Versionamento de Produto
- **Versão:** 1.5.0
- **Release:** `Ultimate Automation + Manual Curatorship Support`
- **Maturidade:** PRODUCTION STABLE
- **Estado:** Implementado e em backup oficial (v2).

---

## 🛠️ Próximas Tarefas de Monitorização e Melhoria
- [x] Validar a criação de um curso completo via JSON no ambiente 8080.
- [x] Otimização de armazenamento (Deduplicação de imagens).
- [ ] Fix do Banco de questões (Contexto do curso).
- [x] Melhoria na extração (Nomes descritivos via legenda JSON).
- [x] Busca flexível (Fuzzy) de imagens.
- [x] Ferramenta de Reparação de imagens v3.1 (Previews + Direct Upload).
- [x] Automação total da extração via Upload.php.
- [ ] **Ajuste de Nomenclatura:** Sincronizar nomes do JSON com os ficheiros extraídos para automação 100%.
- [ ] **Quiz & Aprovação:** Refinar a sincronização de notas e garantir que a aprovação no Quiz dispara a conclusão do curso.
- [ ] **Avaliação do Curso pelo formando:** Implementar o módulo de Feedback.
- [ ] Ligação da API à AI através do Filesystem MCP Server.
- [ ] Verificar integridade das imagens em dispositivos móveis (App Moodle).

  Backend (API Moodle)
   * [x] Criar um web service (create_course_with_content) que aceita um JSON e cria um curso completo.
   * [x] A API consegue criar a estrutura do curso, páginas de conteúdo e quizzes.
   * [x] A API consegue criar bancos de questões e associar perguntas a eles.
   * [ ] (Melhoria) Criar um web service para devolver estatísticas reais (nº de cursos, utilizadores, etc.) para o Dashboard.
   * [ ] (Melhoria) A API create_course_with_content deve devolver URLs absolutas para as imagens para que o Frontend as possa exibir.

  Frontend: Geração de Cursos e Conectividade
   * [x] Criar a rota de API (/api/generate-course) para comunicar com o LLM (exemplo: Gemini) e gerar a estrutura JSON do curso.
   * [x] Implementar a lógica para enviar o JSON do curso gerado para o Moodle através da rota /api/send-to-moodle.
   * [x] Implementar uma UI para upload de ficheiros PDF.
   * [x] Implementar um formulário para configurar os parâmetros de geração do curso (nome, dificuldade, etc.).
   * [x] Criar um componente para pré-visualizar o conteúdo do curso gerado pela IA (course-preview.tsx).
   * [x] Adicionar um mecanismo que verifica e exibe o estado da conexão com o Moodle.
   * [ ] (Nova Funcionalidade) Na UI de geração, adicionar um botão "Copiar Prompt" que permita ao utilizador copiar o prompt final que seria enviado à IA.
   * [ ] (Nova Funcionalidade) Adicionar uma opção/tab para fazer o upload de um ficheiro JSON de curso já existente, contornando a geração por IA e indo direto para o passo de pré-visualização e envio
     para o Moodle.
   * [ ] (Bug/Melhoria) A extração de texto do PDF no frontend (extractTextFromPDF) é um placeholder. A lógica real acontece no backend (/api/generate-course), mas o código do frontend pode ser mais claro
     sobre isso para evitar confusão.

  Frontend: Dashboard e Visualização de Dados
   * [x] Estruturar o layout do dashboard principal.
   * [ ] (Integração) Substituir os dados estáticos do dashboard (stats, recentCourses) por dados reais obtidos através de chamadas à API do Moodle.
   * [ ] (Integração) A lista de "Cursos Recentes" no dashboard deve ser clicável e levar ao detalhe do respetivo curso (course-detail.tsx).
   * [ ] Implementar o componente course-detail.tsx para exibir informações detalhadas de um curso existente no Moodle.

