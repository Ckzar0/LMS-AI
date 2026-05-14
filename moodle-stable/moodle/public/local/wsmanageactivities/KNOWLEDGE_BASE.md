# 📚 Base de Conhecimento - Plugin wsmanageactivities

**Versão**: v1.1.0 (Maio 2026)  
**Moodle**: 5.1.3+ (Estável)  
**Ambiente**: Docker (LMS-AI Ecosystem)

---

## 🗺️ Arquitetura Técnica

### Fluxo de Geração (Headless)
1.  **FrontEnd (Next.js)** processa o PDF via IA e gera um JSON estruturado.
2.  **API** envia o JSON para o Moodle via WebService REST (`local_wsmanageactivities_create_course_with_content`).
3.  **Plugin Moodle** orquestra:
    - Criação do curso e categorias.
    - Criação de secções automáticas.
    - Importação de Conteúdo (Páginas com navegação ←/→).
    - Importação de Quizzes (Banco de Questões Moodle 5.1).
    - Configuração de Critérios de Conclusão.

### Estrutura de Questões (Moodle 5.1+)
O plugin utiliza a nova API de Versionamento de Questões.
- **Tabelas afetadas:** `mdl_question`, `mdl_question_bank_entries`, `mdl_question_versions`.
- **Status padrão:** As questões são criadas com status `ready`.

---

## 🖼️ Motor de Media e Imagens

### 1. Extração via Servidor
O Moodle executa ferramentas de sistema (`poppler-utils`, `imagemagick`, `python3`) para extrair imagens diretamente dos PDFs colocados em `/var/www/Cursos`.

### 2. Ferramenta de Reparação (`fix_images.php`)
Localizada em: `http://[HOST]:8080/local/wsmanageactivities/fix_images.php`
- **Função:** Substituir placeholders `[[IMG_PXX_YY]]` e `[[TABLE_PXX]]`.
- **Persistência:** As imagens validadas são movidas de `extracted_images/` para `course_assets/`.
- **Automação:** Renumera legendas de figuras automaticamente.

---

## 🔑 Configurações de API e Segurança

### WebService Global (LMS AI)
- **Token:** `14c68ff68a1a57cdc4cf4d72f443b87d`
- **Protocolo:** REST (JSON)
- **Função Principal:** `local_wsmanageactivities_create_course_with_content`

### Permissões Críticas
- **Escrita em Disco:** As pastas `extracted_images` e `course_assets` devem ter permissão `777` ou pertencer ao utilizador `www-data`.
- **Capability:** O utilizador do token (Admin) deve ter a capacidade `local/wsmanageactivities:manage`.

---
**Actualizado em: 14/05/2026**
