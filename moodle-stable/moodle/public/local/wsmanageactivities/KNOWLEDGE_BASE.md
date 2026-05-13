# 📚 Base de Conhecimento - Plugin wsmanageactivities

**Versão**: v1.1.0 - Estável  
**Data**: Maio 2026  
**Moodle**: 5.1+  
**Arquitetura**: Dockerized (LMS-AI Ecosystem)

---

## 🗺️ ESTRUTURA DO SISTEMA (DOCKER)

### Ambiente
- **OS**: Alpine/Debian (via Docker)
- **Web Server**: Apache 2.4 (Porta interna 80, Externa 8080)
- **PHP**: 8.2+
- **Database**: MariaDB 10.11 (Nome da BD: `moodle`)

---

## 📂 ESTRUTURA DE DIRETÓRIOS

### Moodle Root (No Contentor)
```
/var/www/html/
├── config.php          # Configuração real do Moodle
└── local/
    └── wsmanageactivities/  # Este plugin
```

### Plugin Directory
```
wsmanageactivities/
├── upload.php          # Interface web de upload (Legada)
├── fix_images.php      # 🖼️ FERRAMENTA CRÍTICA: Ajuste de imagens e legendas
├── externallib.php     # Funções de WebService para Next.js
├── version.php         # Metadados v1.1.0
├── classes/
│   ├── CourseManager.php         # Gestão de cursos
│   ├── QuestionBankManager.php   # Gestão de bancos de questões
│   └── importer/
│       ├── ActivityCreator.php   # Criação de páginas (com navegação)
│       └── QuestionCreator.php   # Criação de questões Moodle 5.1
└── KNOWLEDGE_BASE.md   # Este ficheiro
```

### Logs (Acesso via Docker)
```bash
docker compose logs -f webserver
# Ou interno:
tail -f /var/www/html/moodledata/moodle_error.log
```

---

## 🗄️ BASE DE DADOS

### Tabelas Principais (Moodle 5.1)
```sql
-- Questões (Nova estrutura Moodle 5.0+)
mdl_question
mdl_question_bank_entries      -- Entrada no banco
mdl_question_versions          -- Versionamento (Status: 'ready')
mdl_question_categories
```

---

## ⚙️ FUNCIONALIDADES IMPLEMENTADAS

### 1. Navegação Automática
Todas as páginas criadas incluem botões **← Anterior** e **Próximo →**. Para que isto funcione, o plugin auto-atribui uma **seção diferente** para cada atividade durante a importação.

### 2. Ferramenta de Reparação (`fix_images.php`)
Permite substituir placeholders `[[IMAGE_PXX]]` por imagens reais extraídas ou carregadas, com renumeração automática de figuras.

### 3. Suporte Headless
O plugin expõe a função `local_wsmanageactivities_create_course_with_content` via WebService REST, permitindo que o FrontEnd Next.js orquestre a criação de cursos.

---

## 🔒 SEGURANÇA E ACESSO

### WebService Token
- **Token Padrão**: `14c68ff68a1a57cdc4cf4d72f443b87d`
- **Serviço**: `LMS AI`

### Permissões
- Requer capacidade `moodle/site:config` para aceder às ferramentas de upload e reparação.
- Volumes de imagens (`extracted_images`) devem ter permissões de escrita para o utilizador `www-data`.

---

**Fim da Base de Conhecimento**
