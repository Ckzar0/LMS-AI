# 📝 Changelog - Plugin wsmanageactivities

## v8.2 (14-Feb-2026) - ATUAL ✅

### 🎯 Navegação em Quizzes
- **NEW**: Quizzes agora também têm botões de navegação!
- Botões aparecem na introdução do quiz (antes de iniciar)
- Mesmo estilo das páginas: "← Anterior" e "Próximo →"

### 🔧 Alterações Técnicas
**ActivityCreator.php**:
- Novo método `add_navigation_to_quiz($cm_id, $prev, $next)`
- Adiciona navegação ao campo `intro` do quiz

**upload.php**:
- FASE 2 agora processa páginas E quizzes
- Mensagens separadas: "Navegação página" vs "Navegação quiz"

### 📊 Output Esperado
```
🧭 Adicionando navegação...
   ✅ Navegação página CM 193
   ✅ Navegação página CM 194
   ✅ Navegação quiz CM 195
   ✅ Navegação página CM 196
   ✅ Navegação quiz CM 197
```

---

## v8.1 (14-Feb-2026)

### 🎯 Navegação Corrigida
- **FIX**: Páginas agora navegam para a PRÓXIMA atividade (seja página ou quiz)
- **ANTES**: Página → pula quiz → próxima página ❌
- **AGORA**: Página → quiz → página ✅

### 🔧 Alterações Técnicas
**upload.php**:
- Guarda `$all_cms` com tipo + CM ID de TODAS as atividades
- Navegação olha para índice anterior/próximo no array completo

**ActivityCreator.php**:
- Novo método `get_activity_url($cm_id)` - detecta tipo automaticamente
- Links gerados: `/mod/page/view.php?id=X` ou `/mod/quiz/view.php?id=Y`

### 📄 Documentação
- Toda documentação agora em `/local/wsmanageactivities/*.md`
- README.md principal
- KNOWLEDGE_BASE.md completa
- CHANGELOG.md (este ficheiro)

---

## v8.0 (14-Feb-2026)

### 🧭 Navegação Entre Conteúdos (não seções)
- Implementada navegação em 2 fases
- FASE 1: Criar todas as atividades
- FASE 2: Adicionar navegação às páginas
- Links diretos para CM IDs

---

## v7.2 (14-Feb-2026)

### 🔢 Auto-Incremento de Seções
- Cada atividade em seção diferente (para navegação)
- Base de conhecimento criada

---

## v7.1 (14-Feb-2026)

### 🐛 Bug Fix
- Correção: `array_keys()` → usar `$section->section`

---

## v7.0 (14-Feb-2026)

### 🧭 Botões de Navegação
- Primeira implementação de navegação
- Botões "← Anterior" e "Próximo →"
- Baseado em seções (problema corrigido em v8.0)

---

## v6.1 (14-Feb-2026)

### 🗄️ Moodle 5.0+ Compatibility
- Query SQL compatível com nova estrutura
- Uso de `question_bank_entries` e `question_versions`

---

## v6.0 (14-Feb-2026)

### ⚠️ Tentativa SQL LIMIT
- Revertida - não funcionou

---

## v5.1 (13-Feb-2026)

### 🔄 Gestão de Transações
- `clear_pending_transactions()`
- Evita rollbacks indesejados

---

**Localização**: `/var/www/html/moodle2/public/local/wsmanageactivities/CHANGELOG.md`  
**Acesso SSH**: `ssh ubuntu@192.168.64.2`
