#!/bin/bash
# Script para instalar dependências de sistema no contentor Moodle

echo "📦 Instalando dependências de sistema no contentor Apache..."

# 1. Atualizar repositórios
docker exec -u root moodle-stable-webserver-1 apt-get update

# 2. Instalar ferramentas de extração de PDF (Poppler e ImageMagick)
docker exec -u root moodle-stable-webserver-1 apt-get install -y poppler-utils imagemagick

# 3. Instalar Python e biblioteca de processamento de imagem (Pillow)
docker exec -u root moodle-stable-webserver-1 apt-get install -y python3 python3-pil

echo "✅ Todas as dependências foram instaladas com sucesso!"
