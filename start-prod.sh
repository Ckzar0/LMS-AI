#!/bin/bash
# Script para lançar o ambiente em modo Produção

echo "🚀 A preparar ambiente de Produção..."

# Limpar qualquer instância anterior para evitar conflitos
docker compose down

# Garantir que o .env existe
if [ ! -f .env ]; then
    echo "⚠️ .env não encontrado! A criar a partir do .env.example..."
    cp .env.example .env
fi

echo "🏗️ A buildar imagens otimizadas (isto pode demorar alguns minutos)..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

echo "✅ Ambiente de Produção ativo!"
echo "🔗 Next.js: http://localhost:3001"
echo "🔗 Moodle: http://localhost:8080"
echo ""
echo "Dica: Use 'docker compose logs -f' para acompanhar o arranque."
