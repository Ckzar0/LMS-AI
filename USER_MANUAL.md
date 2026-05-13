# 📖 Manual do Utilizador - AI LMS MOODLE

Este manual descreve o fluxo de trabalho para gestores de formação e administradores, desde a geração do curso via IA até à gestão final no Moodle.

---

## 1. Configuração de Perfil do Curso

Antes de iniciar qualquer geração, deve definir os parâmetros pedagógicos na interface do FrontEnd (Next.js):

*   **Profundidade do Conteúdo:**
    *   **Resumo Executivo:** Focado em pontos-chave e leitura rápida.
    *   **Profissional:** Nível equilibrado para formação corporativa (Padrão).
    *   **Especialista Técnico:** Detalhado, com foco em procedimentos e normas técnicas.
*   **Configuração do Quiz:**
    *   **Dificuldade:** Fácil, Média ou Difícil.
    *   **Número de Questões:** Define quantas questões o aluno terá de responder. 
    *   *Nota: O sistema gera automaticamente **+10 questões extra** no banco para permitir aleatoriedade.*
*   **Módulos Opcionais:** Ative ou desative os botões de **Quizzes**, **Feedback** e **Certificação** conforme a necessidade do curso.

---

## 2. Métodos de Geração de Conteúdo

### A. Geração Automática (Via PDF)
Ideal para criar cursos novos a partir de manuais existentes.
1.  Faça o upload do manual em PDF.
2.  Clique em **"Gerar Curso com IA"**.
3.  O sistema irá extrair o texto e imagens, enviar para a Portkey (IA) e criar uma proposta de curso.
4.  Visualize o curso no **Preview** antes de enviar para o Moodle.

### B. Modo Fábrica (Via JSON + PDF)
Ideal para importar cursos já estruturados ou sessões guardadas.
1.  No separador **"Gerar com JSON"**, carregue o ficheiro `.json` do curso.
2.  **Obrigatório:** Carregue também o PDF original para que o sistema possa extrair as imagens referenciadas no JSON.
3.  O sistema processa as imagens automaticamente e prepara o curso para criação.

### C. Cópia de Prompt (Fluxo Externo)
Se preferir usar um LLM externo (Claude, ChatGPT, etc.):
1.  Configure as opções de profundidade e quiz.
2.  Clique em **"Copiar Prompt"**.
3.  Cole no seu LLM, obtenha o JSON de resposta e utilize o "Modo Fábrica" (Ponto B) para finalizar.

---

## 3. Gestão e Edição no Moodle (BackEnd)

O FrontEnd serve para a criação rápida, mas o **Moodle** é a "oficina" para ajustes finos:

### A. Ferramenta de Ajuste de Imagens (`fix_images.php`)
Esta é a ferramenta principal para o polimento visual do curso:
*   **Acesso:** `http://[IP_DO_SERVIDOR]:8080/local/wsmanageactivities/fix_images.php`
*   **Funcionalidades:**
    *   **Substituição de Placeholders:** Se a IA colocou uma imagem que não condiz com o texto, pode escolher a imagem correta da biblioteca de extração ou fazer upload de uma nova.
    *   **Renumeração Automática:** Ao adicionar ou remover imagens, o sistema renumera automaticamente todas as figuras ("Figura 1", "Figura 2", etc.) por ordem de aparecimento.
    *   **Limpeza de Legendas:** O sistema limpa automaticamente repetições e formata as legendas para um aspeto profissional.
    *   **Persistência:** As imagens selecionadas são movidas para uma pasta permanente do curso (`course_assets/`), garantindo que nunca se perdem.

### B. Outras Tarefas de Manutenção
*   **Gerir Questões:** Pode trocar ou apagar questões do Banco de Questões gerado diretamente na interface de Quizzes do Moodle.
*   **Manutenção Global:** Na interface do plugin `wsmanageactivities`, tem acesso a:
    *   **Apagar Curso:** Remoção total do curso e ficheiros associados.
    *   **Editar Estrutura:** Alterar nomes de seções ou conteúdos diretamente no editor do Moodle.

---

## 4. Experiência do Aluno (Study Mode)

Os alunos utilizam preferencialmente a interface moderna do Next.js:

1.  **Dashboard:** O aluno visualiza os cursos onde está inscrito e o seu progresso real.
2.  **Visualizador de Conteúdo:**
    *   Interface limpa e focada no estudo.
    *   Navegação intuitiva através de botões **← Anterior** e **Próximo →**.
3.  **Avaliação:**
    *   Realização de Quizzes com feedback imediato.
    *   Preenchimento do inquérito de satisfação (se ativo).
4.  **Conclusão:** Após completar todos os módulos e atingir a nota mínima no quiz, o aluno pode descarregar o seu **Certificado PDF** diretamente do dashboard.

---

## 5. Dicas de Sucesso
*   **Imagens:** Garanta que o PDF tem boa qualidade para que a extração de diagramas seja precisa.
*   **Prompts:** Se o curso gerado for muito curto, tente aumentar a "Profundidade" para "Especialista Técnico".
*   **Sincronização:** Se o FrontEnd não mostrar o curso novo, utilize a opção "Purge Caches" no Moodle.
