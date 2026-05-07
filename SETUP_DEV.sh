#!/bin/bash

echo "🚀 Iniciando Setup de Desenvolvimento - AI LMS Moodle"

# 1. Verificar estrutura de pastas
if [ ! -d "moodle-stable/moodle/public/local/wsmanageactivities" ]; then
    echo "❌ Erro: Pasta do plugin não encontrada em moodle-stable/moodle/public/local/wsmanageactivities"
    exit 1
fi

# 2. Garantir permissões nas pastas de extração
echo "📂 Configurando permissões de escrita para imagens..."
mkdir -p moodle-stable/moodle/public/local/wsmanageactivities/extracted_images
chmod -R 777 moodle-stable/moodle/public/local/wsmanageactivities/extracted_images
mkdir -p moodle-stable/moodle/course_assets
chmod -R 777 moodle-stable/moodle/course_assets

# 3. Validar Moodle no Docker
echo "🐳 Verificando containers..."
if [ $(docker ps | grep moodle-stable-webserver-1 | wc -l) -eq 0 ]; then
    echo "⚠️ Aviso: O container Docker não parece estar a correr. Inicie-o com ./start-moodle.sh"
else
    echo "✅ Docker está ativo."
    # Forçar a limpeza da cache do Moodle para reconhecer alterações no código
    docker exec moodle-stable-webserver-1 php /var/www/html/admin/cli/purge_caches.php
fi

# 4. Configurar FrontEnd
echo "⚛️ Verificando FrontEnd..."
if [ -f "FrontEnd/package.json" ]; then
    echo "✅ FrontEnd encontrado."
    # Verificar se as variáveis de ambiente existem
    if [ ! -f "FrontEnd/.env.local" ]; then
        echo "📝 Criando .env.local básico para o FrontEnd..."
        echo "MOODLE_URL=http://localhost:8080" > FrontEnd/.env.local
        echo "MOODLE_TOKEN=14c68ff68a1a57cdc4cf4d72f443b87d" >> FrontEnd/.env.local
        echo "NEXT_PUBLIC_MOODLE_URL=http://localhost:8080" >> FrontEnd/.env.local
    fi
fi

echo "✨ Setup concluído! O projeto está pronto para desenvolvimento."
