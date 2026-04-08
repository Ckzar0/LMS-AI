#!/bin/bash
# Script para forçar reinstalação do plugin

echo "🔄 FORÇANDO REINSTALAÇÃO DO PLUGIN"
echo "================================="

# Verificar se estamos no diretório correto
if [ ! -f "version.php" ]; then
    echo "❌ Execute este script do diretório do plugin"
    exit 1
fi

# Atualizar versão para forçar upgrade
CURRENT_DATE=$(date +%Y%m%d)
NEW_VERSION="${CURRENT_DATE}02"  # Incrementar para forçar update

echo "📋 Atualizando versão para: $NEW_VERSION"

# Backup e atualizar version.php
cp version.php version.php.backup
sed -i.bak "s/\$plugin->version = [0-9]*;/\$plugin->version = $NEW_VERSION;/" version.php

echo "✅ Versão atualizada"
echo ""
echo "📌 PRÓXIMOS PASSOS:"
echo "1. Aceder à interface admin do Moodle"
echo "2. Ir para Site Administration → Notifications"
echo "3. Executar upgrade do plugin"
echo "4. Verificar se funções aparecem nos web services"
echo ""
echo "🔗 URL Admin: http://localhost:8888/moodle500/admin/index.php"
