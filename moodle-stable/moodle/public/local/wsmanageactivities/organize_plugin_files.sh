#!/bin/bash
# Script para organizar e renomear ficheiros do plugin local_wsmanageactivities

echo "🔧 ORGANIZANDO FICHEIROS DO PLUGIN"
echo "=================================="

# Verificar se estamos na pasta correta
if [[ ! -f "plugin_version.php" ]]; then
    echo "❌ Erro: Execute este script na pasta onde estão os ficheiros do plugin"
    exit 1
fi

# Criar estrutura de diretórios
echo "📁 Criando estrutura de diretórios..."
mkdir -p classes/external
mkdir -p classes/local  
mkdir -p classes/privacy
mkdir -p db
mkdir -p lang/en
mkdir -p lang/pt
mkdir -p tests
mkdir -p scripts

echo "✅ Diretórios criados"

# Renomear e mover ficheiros base
echo "📄 Organizando ficheiros base..."
if [[ -f "plugin_version.php" ]]; then
    cp plugin_version.php version.php
    echo "✅ version.php"
fi

if [[ -f "plugin_externallib.php" ]]; then
    cp plugin_externallib.php externallib.php
    echo "✅ externallib.php"
fi

# Mover ficheiros db/
echo "📄 Organizando ficheiros db/..."
if [[ -f "plugin_services.php" ]]; then
    cp plugin_services.php db/services.php
    echo "✅ db/services.php"
fi

if [[ -f "plugin_access.php" ]]; then
    cp plugin_access.php db/access.php
    echo "✅ db/access.php"
fi

if [[ -f "plugin_install.php" ]]; then
    cp plugin_install.php db/install.php
    echo "✅ db/install.php"
fi

# Mover ficheiros classes/external/
echo "📄 Organizando ficheiros classes/external/..."
if [[ -f "plugin_create_page.php" ]]; then
    cp plugin_create_page.php classes/external/create_page.php
    echo "✅ classes/external/create_page.php"
fi

if [[ -f "plugin_create_quiz.php" ]]; then
    cp plugin_create_quiz.php classes/external/create_quiz.php
    echo "✅ classes/external/create_quiz.php"
fi

if [[ -f "plugin_add_quiz_questions.php" ]]; then
    cp plugin_add_quiz_questions.php classes/external/add_quiz_questions.php
    echo "✅ classes/external/add_quiz_questions.php"
fi

if [[ -f "plugin_get_module_types.php" ]]; then
    cp plugin_get_module_types.php classes/external/get_module_types.php
    echo "✅ classes/external/get_module_types.php"
fi

if [[ -f "plugin_base_external.php" ]]; then
    cp plugin_base_external.php classes/external/base_external.php
    echo "✅ classes/external/base_external.php"
fi

# Mover ficheiros classes/local/
echo "📄 Organizando ficheiros classes/local/..."
if [[ -f "plugin_validation.php" ]]; then
    cp plugin_validation.php classes/local/validation.php
    echo "✅ classes/local/validation.php"
fi

if [[ -f "plugin_page_helper.php" ]]; then
    cp plugin_page_helper.php classes/local/page_helper.php
    echo "✅ classes/local/page_helper.php"
fi

if [[ -f "plugin_quiz_helper.php" ]]; then
    cp plugin_quiz_helper.php classes/local/quiz_helper.php
    echo "✅ classes/local/quiz_helper.php"
fi

# Mover ficheiro privacy
echo "📄 Organizando ficheiro privacy/..."
if [[ -f "plugin_privacy_provider.php" ]]; then
    cp plugin_privacy_provider.php classes/privacy/provider.php
    echo "✅ classes/privacy/provider.php"
fi

# Mover ficheiros de idioma
echo "📄 Organizando ficheiros de idioma..."
if [[ -f "plugin_lang_en.php" ]]; then
    cp plugin_lang_en.php lang/en/local_wsmanageactivities.php
    echo "✅ lang/en/local_wsmanageactivities.php"
fi

if [[ -f "plugin_lang_pt.php" ]]; then
    cp plugin_lang_pt.php lang/pt/local_wsmanageactivities.php
    echo "✅ lang/pt/local_wsmanageactivities.php"
fi

# Mover testes
echo "📄 Organizando testes..."
if [[ -f "plugin_external_test.php" ]]; then
    cp plugin_external_test.php tests/external_test.php
    echo "✅ tests/external_test.php"
fi

# Mover scripts
echo "📄 Organizando scripts..."
if [[ -f "plugin_test_script.sh" ]]; then
    cp plugin_test_script.sh scripts/test_plugin_complete.sh
    chmod +x scripts/test_plugin_complete.sh
    echo "✅ scripts/test_plugin_complete.sh"
fi

if [[ -f "plugin_install_script.sh" ]]; then
    cp plugin_install_script.sh scripts/install_plugin.sh
    chmod +x scripts/install_plugin.sh
    echo "✅ scripts/install_plugin.sh"
fi

# Mover documentação
echo "📄 Organizando documentação..."
if [[ -f "plugin_changelog.md" ]]; then
    cp plugin_changelog.md CHANGELOG.md
    echo "✅ CHANGELOG.md"
fi

# Criar README.md básico se não existir
if [[ ! -f "README.md" ]]; then
    cat > README.md << 'EOF'
# Plugin Web Services Activity Management

**local_wsmanageactivities** - Plugin Moodle para criação automática de atividades via API

## Instalação Rápida

1. Copiar esta pasta para `moodle/local/wsmanageactivities/`
2. Aceder à interface admin do Moodle para instalação
3. Configurar web services (adicionar 4 funções ao token)
4. Testar com: `bash scripts/test_plugin_complete.sh`

## Web Services Disponíveis

- `local_wsmanageactivities_create_page` - Criar páginas
- `local_wsmanageactivities_create_quiz` - Criar quizzes  
- `local_wsmanageactivities_add_quiz_questions` - Adicionar questões
- `local_wsmanageactivities_get_module_types` - Listar módulos

## Compatibilidade

- Moodle 5.0+
- PHP 8.1+
- Tokens existentes do sistema de automação

Consulte a documentação completa nos ficheiros markdown incluídos.
EOF
    echo "✅ README.md criado"
fi

# Mostrar estrutura final
echo ""
echo "📋 ESTRUTURA FINAL CRIADA:"
echo "=========================="
find . -name "*.php" -o -name "*.md" -o -name "*.sh" | grep -v "plugin_" | sort

echo ""
echo "🎯 PRÓXIMOS PASSOS:"
echo "1. Copiar toda esta pasta para: /caminho/para/moodle/local/wsmanageactivities/"
echo "2. Aceder ao admin Moodle para instalação"
echo "3. Configurar web services"
echo "4. Testar com: bash scripts/test_plugin_complete.sh"

echo ""
echo "✅ ORGANIZAÇÃO COMPLETA!"