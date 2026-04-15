#!/bin/bash
# Script para reinstalar plugin completamente

PLUGIN_PATH="/Applications/MAMP/htdocs/moodle500/local/wsmanageactivities"
MOODLE_PATH="/Applications/MAMP/htdocs/moodle500"

echo "🔄 REINSTALAÇÃO COMPLETA DO PLUGIN"
echo "=================================="

echo "1. Fazendo backup..."
cp -r "$PLUGIN_PATH" "$PLUGIN_PATH.backup.$(date +%s)"

echo "2. Removendo plugin..."
rm -rf "$PLUGIN_PATH"

echo "3. Aguardando 5 segundos..."
sleep 5

echo "4. Restaurando plugin..."
cp -r "$PLUGIN_PATH.backup"* "$PLUGIN_PATH"

echo "5. Limpando cache..."
rm -rf "$MOODLE_PATH/cache"/*
rm -rf "$MOODLE_PATH/localcache"/*
rm -rf "$MOODLE_PATH/temp"/*

echo "6. Tocando config.php..."
touch "$MOODLE_PATH/config.php"

echo "✅ Plugin reinstalado. Verificar interface admin."
