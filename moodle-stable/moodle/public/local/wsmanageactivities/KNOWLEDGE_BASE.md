# 📚 Base de Conhecimento - Plugin wsmanageactivities

**Versão**: 7.2  
**Data**: 14-Feb-2026  
**Moodle**: 5.0+  
**Autor**: Sistema de importação automática de cursos

---

## 🗺️ ESTRUTURA DO SISTEMA

### Servidor
- **IP**: 192.168.64.2
- **OS**: Ubuntu 24.04 LTS
- **Web Server**: Apache 2.4
- **PHP**: 8.1+
- **Database**: MariaDB 12.1.2

### SSH
```bash
ssh ubuntu@192.168.64.2
# Porta: 22 (padrão)
```

---

## 📂 ESTRUTURA DE DIRETÓRIOS

### Moodle Root
```
/var/www/html/moodle2/
├── public/              # Document root do Apache
│   ├── config.php       # Loader (aponta para ../config.php)
│   └── local/
│       └── wsmanageactivities/  # Este plugin
└── config.php          # Configuração real do Moodle
```

### Plugin Directory
```
/var/www/html/moodle2/public/local/wsmanageactivities/
├── upload.php          # Interface web de upload
├── version.php         # Metadados do plugin
├── classes/
│   ├── CourseManager.php         # Gestão de cursos
│   ├── QuestionBankManager.php   # Gestão de bancos de questões
│   └── importer/
│       ├── ActivityCreator.php   # Criação de páginas e quizzes
│       └── QuestionCreator.php   # Criação de questões
└── KNOWLEDGE_BASE.md   # Este ficheiro
```

### Logs
```bash
# Logs PHP
/var/log/php/error.log
sudo tail -f /var/log/php/error.log

# Logs Apache
/var/log/apache2/error.log
/var/log/apache2/moodle_access.log
sudo tail -f /var/log/apache2/error.log
```

---

## 🗄️ BASE DE DADOS

### Configuração (em /var/www/html/moodle2/config.php)
```php
$CFG->dbtype    = 'mariadb';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle2';        // ⚠️ IMPORTANTE: moodle2, NÃO moodle
$CFG->dbuser    = 'moodleuser2';
$CFG->dbpass    = '[senha]';
$CFG->prefix    = 'mdl_';
```

### Acesso à Base de Dados
```bash
# CORRETO - usar moodle2
sudo mariadb -u root -proot moodle2

# ERRADO - NÃO usar 'moodle'
sudo mariadb -u root -proot moodle  # ❌
```

### Tabelas Principais
```sql
-- Cursos
mdl_course

-- Módulos de curso (atividades)
mdl_course_modules
mdl_modules

-- Páginas
mdl_page

-- Quizzes
mdl_quiz
mdl_quiz_slots

-- Questões (Moodle 5.0+ - nova estrutura)
mdl_question
mdl_question_bank_entries      -- Nova tabela Moodle 5.0
mdl_question_versions          -- Nova tabela Moodle 5.0
mdl_question_categories
mdl_question_answers

-- Seções do curso
mdl_course_sections
```

---

## 🌐 URLs DE ACESSO

### Interface de Upload
```
http://192.168.64.2/moodle2/local/wsmanageactivities/upload.php
```

### Ver Curso Criado
```
http://192.168.64.2/moodle2/course/view.php?id=COURSE_ID
```

### Ver Página Específica
```
http://192.168.64.2/moodle2/mod/page/view.php?id=CM_ID
```

### Administração Moodle
```
http://192.168.64.2/moodle2/admin/
```

---

## 🔑 PERMISSÕES

### Usuário/Grupo Web
```bash
# Proprietário dos ficheiros
User: www-data
Group: www-data

# Permissões típicas
Ficheiros: 644
Diretórios: 755

# Corrigir permissões
sudo chown -R www-data:www-data /var/www/html/moodle2/public/local/wsmanageactivities/
sudo find /var/www/html/moodle2/public/local/wsmanageactivities/ -type f -exec chmod 644 {} \;
sudo find /var/www/html/moodle2/public/local/wsmanageactivities/ -type d -exec chmod 755 {} \;
```

---

## 📝 ESTRUTURA DO JSON DE IMPORTAÇÃO

### Estrutura Mínima
```json
{
  "course_name": "Nome do Curso",
  "course_shortname": "SHORTNAME",
  "course_summary": "Descrição do curso",
  "question_banks": [
    {
      "name": "Banco de Questões - Tema",
      "questions": [
        {
          "name": "Questão 1",
          "questiontext": "Texto da questão?",
          "qtype": "multichoice",
          "answers": [
            {"text": "Resposta A", "fraction": 0},
            {"text": "Resposta B (correta)", "fraction": 1}
          ]
        }
      ]
    }
  ],
  "activities": [
    {
      "type": "page",
      "name": "Introdução",
      "intro": "Descrição breve",
      "content": "<h2>Conteúdo HTML</h2>",
      "section": 1
    },
    {
      "type": "quiz",
      "name": "Quiz Final",
      "intro": "Teste seus conhecimentos",
      "section": 2,
      "questions_from_bank": {
        "bank_name": "Banco de Questões - Tema",
        "count": 5
      }
    }
  ]
}
```

### Campo section (Importante!)
```json
// ⚠️ Se não especificar 'section', o sistema auto-atribui:
// Atividade 1 → section: 1
// Atividade 2 → section: 2
// Atividade 3 → section: 3
// etc...

// Isto é ESSENCIAL para a navegação funcionar!
```

---

## ⚙️ FUNCIONALIDADES IMPLEMENTADAS

### 1. Auto-Incremento de Seções (v7.2)
**Ficheiro**: `upload.php` (linhas ~53-59)

```php
// Auto-atribuir seções diferentes para cada atividade (para navegação)
$section_counter = 1;
foreach ($data['activities'] as &$activity) {
    if (!isset($activity['section']) || $activity['section'] === 0) {
        $activity['section'] = $section_counter++;
    }
}
unset($activity);
```

**Objetivo**: Garantir que cada atividade fica numa seção diferente para permitir navegação entre páginas.

### 2. Botões de Navegação (v7.0)
**Ficheiro**: `ActivityCreator.php` → `add_navigation_buttons()`

**Funcionalidade**: Adiciona botões "← Anterior" e "Próximo →" no final de cada página.

**HTML Gerado**:
```html
<div style="margin-top: 40px; padding: 20px; border-top: 2px solid #ddd; 
     display: flex; justify-content: space-between; align-items: center;">
  <a href="/moodle2/course/view.php?id=CURSO&section=1">← Anterior</a>
  <a href="/moodle2/course/view.php?id=CURSO&section=3">Próximo →</a>
</div>
```

**Lógica**:
- Primeira página: Só "Próximo →"
- Páginas intermédias: "← Anterior" e "Próximo →"
- Última página: Só "← Anterior"

### 3. Gestão de Transações (v5.1)
**Ficheiro**: `ActivityCreator.php` → `clear_pending_transactions()`

**Objetivo**: Evitar conflitos de transações pendentes que causavam rollbacks.

### 4. Suporte Moodle 5.0+ (v6.1)
**Tabelas Novas**:
- `question_bank_entries` (substitui category direto em question)
- `question_versions` (versionamento de questões)

**Query Corrigida**:
```sql
SELECT q.id 
FROM {question} q
JOIN {question_versions} qv ON qv.questionid = q.id
JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
WHERE qbe.questioncategoryid = :catid 
  AND qv.status = 'ready'
ORDER BY RAND()
```

---

## 🧪 COMANDOS DE TESTE

### Validar Sintaxe PHP
```bash
php -l /var/www/html/moodle2/public/local/wsmanageactivities/upload.php
php -l /var/www/html/moodle2/public/local/wsmanageactivities/classes/importer/ActivityCreator.php
```

### Ver Logs em Tempo Real
```bash
sudo tail -f /var/log/php/error.log
```

### Limpar Logs
```bash
sudo truncate -s 0 /var/log/php/error.log
```

### Verificar Curso Criado
```bash
# Listar cursos recentes
sudo mariadb -u root -proot moodle2 -e "
SELECT id, shortname, fullname 
FROM mdl_course 
ORDER BY id DESC 
LIMIT 5;
"

# Ver atividades de um curso
sudo mariadb -u root -proot moodle2 -e "
SELECT cm.id, m.name as type, cm.section, cm.instance
FROM mdl_course_modules cm
JOIN mdl_modules m ON m.id = cm.module
WHERE cm.course = COURSE_ID
ORDER BY cm.section, cm.id;
"

# Ver conteúdo de uma página
sudo mariadb -u root -proot moodle2 -e "
SELECT id, name, LENGTH(content) as len, 
       RIGHT(content, 200) as final_part
FROM mdl_page 
WHERE course = COURSE_ID
LIMIT 1\G
"
```

---

## 🐛 TROUBLESHOOTING

### Problema: Páginas sem conteúdo
**Causa**: Campo `content` vazio no JSON ou perdido durante processamento  
**Solução**: Verificar JSON e logs

### Problema: Botões de navegação não aparecem
**Causa**: Todas as atividades na mesma seção  
**Solução**: ✅ CORRIGIDO em v7.2 - auto-incremento de seções

### Problema: "Error reading from database"
**Causa**: Query SQL incompatível com Moodle 5.0  
**Solução**: ✅ CORRIGIDO em v6.1 - usar question_bank_entries

### Problema: Transações pendentes
**Causa**: Transações não finalizadas de operações anteriores  
**Solução**: ✅ CORRIGIDO em v5.1 - clear_pending_transactions()

### Problema: Curso criado mas sem módulos
**Causa**: Rollback automático de transação  
**Solução**: Verificar logs para identificar erro

---

## 📊 HISTÓRICO DE VERSÕES

### v7.2 (14-Feb-2026)
- ✅ Auto-incremento de seções para navegação
- ✅ Base de conhecimento criada

### v7.1 (14-Feb-2026)
- ✅ Fix: array_keys → usar $section->section
- ✅ Correção da extração de números de seção

### v7.0 (14-Feb-2026)
- ✅ Botões de navegação entre páginas

### v6.1 (14-Feb-2026)
- ✅ Query SQL compatível com Moodle 5.0
- ✅ Suporte a question_bank_entries

### v6.0 (14-Feb-2026)
- ✅ Tentativa de LIMIT em SQL (revertida)

### v5.1 (13-Feb-2026)
- ✅ Gestão de transações pendentes
- ✅ Clear pending transactions

---

## 🔒 SEGURANÇA

### Acesso ao Upload
- Requer login no Moodle
- Requer capacidade `moodle/site:config`
- Validação de sesskey (CSRF protection)

### Validação de Dados
- JSON validado antes de processar
- Sanitização de inputs HTML
- Escape de queries SQL via Moodle DML

---

## 🚀 MELHORIAS FUTURAS

### Navegação
- [ ] Ícones em vez de texto (Font Awesome)
- [ ] Responsivo para mobile
- [ ] Efeitos hover
- [ ] ARIA labels para acessibilidade
- [ ] Tradução i18n

### Funcionalidades
- [ ] Preview antes de criar curso
- [ ] Edição de cursos existentes
- [ ] Exportação para JSON
- [ ] Templates de cursos
- [ ] Bulk import (múltiplos JSONs)

### Performance
- [ ] Cache de queries repetidas
- [ ] Batch insert de questões
- [ ] Processamento assíncrono

---

## 📞 SUPORTE

### Logs
Sempre verificar logs primeiro:
```bash
sudo tail -100 /var/log/php/error.log
```

### Debug Mode
Ativar em `config.php`:
```php
$CFG->debug = 32767;
$CFG->debugdisplay = 1;
```

### Backups
Antes de modificações importantes:
```bash
# Backup da BD
sudo mysqldump -u root -proot moodle2 > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup do plugin
cd /var/www/html/moodle2/public/local/
sudo tar -czf wsmanageactivities_backup_$(date +%Y%m%d_%H%M%S).tar.gz wsmanageactivities/
```

---

**Fim da Base de Conhecimento**
