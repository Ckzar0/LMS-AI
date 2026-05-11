#!/bin/bash

# =================================================================
# 🚀 BOOTSTRAP - SETUP INTEGRAL AI LMS MOODLE
# Este script prepara o ambiente do zero num computador novo.
# =================================================================

set -e

echo "🌟 Iniciando Setup Automático..."

# 1. Verificar Requisitos
command -v docker >/dev/null 2>&1 || { echo >&2 "❌ Erro: Docker não está instalado."; exit 1; }

# 2. Verificar Moodle Core
if [ ! -f "moodle-stable/moodle/index.php" ]; then
    echo "📥 Moodle Core não encontrado. A clonar do repositório oficial (Moodle 4.5)..."
    git clone --depth 1 --branch v4.5.0 https://github.com/moodle/moodle.git moodle-temp
    cp -rv moodle-temp/* moodle-stable/moodle/
    rm -rf moodle-temp
fi

# 3. Garantir pastas de dados e permissões (Host Side)
echo "📂 Preparando pastas e permissões no host..."
mkdir -p moodle-stable/moodle/public/local/wsmanageactivities/extracted_images
mkdir -p moodle-stable/moodle/public/local/wsmanageactivities/temp_pdfs
mkdir -p moodle-stable/moodle/course_assets
mkdir -p moodle-stable/mariadb_data
chmod -R 777 moodle-stable/moodle/public/local/wsmanageactivities/extracted_images
chmod -R 777 moodle-stable/moodle/public/local/wsmanageactivities/temp_pdfs
chmod -R 777 moodle-stable/moodle/course_assets

# Sincronizar Prompt de Geração
echo "📝 Sincronizando Prompt de Geração..."
cp Prompts/PROMPT_GERACAO_CURSO.md moodle-stable/moodle/public/local/wsmanageactivities/master_prompt.md || echo "⚠️ Aviso: Ficheiro de prompt não encontrado."

# 4. Iniciar Contentores
echo "🐳 A subir contentores (Build & Up)..."
if docker compose version >/dev/null 2>&1; then
    docker compose up -d --build
else
    docker-compose up -d --build
fi

# Obter nomes dos contentores
DB_CONTAINER=$(docker ps --format '{{.Names}}' | grep db | head -n 1)
WEBSERVER_CONTAINER=$(docker ps --format '{{.Names}}' | grep webserver | head -n 1)

# 5. Aguardar Banco de Dados
echo "⏳ Aguardando que a base de dados aceite ligações..."
until docker exec $DB_CONTAINER mariadb -u moodle -pm@0dl3ing -e "select 1" >/dev/null 2>&1; do
    sleep 2
done
echo "✅ Base de Dados pronta!"

# 6. Instalar Ferramentas de Extração (Extração de PDF e Imagens)
echo "🛠️ Instalando ferramentas de sistema no contentor (poppler, python)..."
docker exec -u root $WEBSERVER_CONTAINER apt-get update
docker exec -u root $WEBSERVER_CONTAINER apt-get install -y poppler-utils python3

# Garantir permissões internas (Docker Side)
docker exec -u root $WEBSERVER_CONTAINER chown -R www-data:www-data /var/www/html/public/local/wsmanageactivities/extracted_images
docker exec -u root $WEBSERVER_CONTAINER chown -R www-data:www-data /var/www/html/public/local/wsmanageactivities/temp_pdfs

# 7. Instalação / Configuração do Moodle
TABLE_COUNT=$(docker exec $DB_CONTAINER mariadb -u moodle -pm@0dl3ing moodle -e "show tables" | wc -l)

if [ $TABLE_COUNT -le 1 ]; then
    echo "🆕 Base de dados vazia. A iniciar instalação limpa..."
    if [ -f "moodle_base_setup.sql" ]; then
        echo "📦 Restaurando base de dados pré-configurada (Clean Base)..."
        docker exec -i $DB_CONTAINER mariadb -u moodle -pm@0dl3ing moodle < moodle_base_setup.sql
    else
        echo "🔨 Executando CLI Installer..."
        docker exec $WEBSERVER_CONTAINER php admin/cli/install_database.php \
            --lang=pt \
            --adminuser=admin \
            --adminpass=admin \
            --adminemail=admin@example.com \
            --agree-license \
            --fullname="AI LMS Moodle" \
            --shortname="LMS-AI"
    fi
else
    echo "✅ Base de dados já contém dados. A saltar instalação."
fi

# 8. Ativação Vital (WebServices e Plugins)
echo "⚙️ Aplicando configurações de sistema e registrando plugins..."
# Ativar WebServices e Protocolo REST
docker exec $DB_CONTAINER mariadb -u moodle -pm@0dl3ing moodle -e "UPDATE m_config SET value = '1' WHERE name = 'enablewebservices';"
docker exec $DB_CONTAINER mariadb -u moodle -pm@0dl3ing moodle -e "UPDATE m_config SET value = '1' WHERE name = 'webservice_rest_enabled';"

# Forçar upgrade para registar o plugin local_wsmanageactivities
docker exec $WEBSERVER_CONTAINER php admin/cli/upgrade.php --non-interactive

# Configurar o serviço 'LMS AI' e o Token
echo "🔑 Configurando WebService Token e Funções..."
docker exec $DB_CONTAINER mariadb -u moodle -pm@0dl3ing moodle -e "
INSERT IGNORE INTO m_external_services (name, shortname, enabled, requiredcapability, restrictedusers, timecreated, timemodified) 
VALUES ('LMS AI', 'lms_ai', 1, NULL, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

SET @service_id = (SELECT id FROM m_external_services WHERE shortname = 'lms_ai');

INSERT IGNORE INTO m_external_services_functions (externalserviceid, functionname) VALUES 
(@service_id, 'local_wsmanageactivities_create_course_with_content'),
(@service_id, 'core_webservice_get_site_info'),
(@service_id, 'core_course_get_courses'),
(@service_id, 'core_course_get_contents'),
(@service_id, 'core_user_get_users');

SET @admin_id = (SELECT id FROM m_user WHERE username = 'admin');
INSERT IGNORE INTO m_external_tokens (token, tokentype, contextid, externalserviceid, userid, creatorid, timecreated, name)
VALUES ('14c68ff68a1a57cdc4cf4d72f443b87d', 1, 1, @service_id, @admin_id, @admin_id, UNIX_TIMESTAMP(), 'LMS AI Token');
"

# 9. Setup do FrontEnd
echo "⚛️ Configurando FrontEnd..."
if [ ! -f "FrontEnd/.env.local" ]; then
    echo "📝 Criando .env.local para o FrontEnd..."
    cat > FrontEnd/.env.local <<EOL
MOODLE_URL=http://localhost:8080
MOODLE_TOKEN=14c68ff68a1a57cdc4cf4d72f443b87d
NEXT_PUBLIC_MOODLE_URL=http://localhost:8080
GEMINI_API_KEY=YOUR_API_KEY_HERE
EOL
fi

# 10. Finalização
docker exec $WEBSERVER_CONTAINER php admin/cli/purge_caches.php

echo "✨ SETUP CONCLUÍDO COM SUCESSO!"
echo "-------------------------------------------------------"
echo "🌐 Moodle: http://localhost:8080 (admin / admin)"
echo "🚀 FrontEnd: http://localhost:3000"
echo "-------------------------------------------------------"
