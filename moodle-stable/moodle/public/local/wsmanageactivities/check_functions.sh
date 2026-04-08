#!/bin/bash
# Verificar se as funções estão registadas

MOODLE_URL="http://localhost:8888/moodle500"
MOBILE_TOKEN="d1fcf3a7a21bb341c2831c90abd0d334"

echo "🔍 VERIFICANDO FUNÇÕES REGISTADAS"
echo "================================"

echo "📋 Obtendo lista de funções disponíveis..."

FUNCTIONS_LIST=$(curl -s "${MOODLE_URL}/webservice/rest/server.php" \
  -d "wstoken=${MOBILE_TOKEN}" \
  -d "wsfunction=core_webservice_get_site_info" \
  -d "moodlewsrestformat=json")

echo ""
echo "🔍 Procurando funções do plugin wsmanageactivities..."

if echo "$FUNCTIONS_LIST" | jq -e '.functions' > /dev/null 2>&1; then
    PLUGIN_FUNCTIONS=$(echo "$FUNCTIONS_LIST" | jq -r '.functions[] | select(.name | contains("wsmanageactivities")) | .name')
    
    if [ -z "$PLUGIN_FUNCTIONS" ]; then
        echo "❌ Nenhuma função do plugin encontrada"
        echo ""
        echo "💡 SOLUÇÕES:"
        echo "1. Execute: ./force_reinstall.sh"
        echo "2. Upgrade via interface admin"
        echo "3. Verificar se plugin está ativo"
    else
        echo "✅ Funções encontradas:"
        echo "$PLUGIN_FUNCTIONS" | sed 's/^/  - /'
        
        echo ""
        echo "📊 TESTE RÁPIDO:"
        echo "Testando função get_module_types..."
        
        TEST_RESULT=$(curl -s "${MOODLE_URL}/webservice/rest/server.php" \
          -d "wstoken=${MOBILE_TOKEN}" \
          -d "wsfunction=local_wsmanageactivities_get_module_types" \
          -d "courseid=1" \
          -d "moodlewsrestformat=json")
        
        if echo "$TEST_RESULT" | jq -e '.modules' > /dev/null 2>&1; then
            echo "✅ Plugin funcional!"
        else
            echo "⚠️ Plugin registado mas com problemas:"
            echo "$TEST_RESULT" | jq -r '.message // "Erro desconhecido"'
        fi
    fi
else
    echo "❌ Erro ao obter lista de funções"
    echo "Verificar token e conectividade"
fi
