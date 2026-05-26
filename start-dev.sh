#!/bin/bash
# Script para lançar o ambiente em modo Desenvolvimento (Bind Mounts ativos)

echo "🛠️ A preparar ambiente de Desenvolvimento..."

# Limpar qualquer instância anterior para evitar conflitos
docker compose down

# Garantir que o .env existe
if [ ! -f .env ]; then
    echo "⚠️ .env não encontrado! A criar a partir do .env.example..."
    cp .env.example .env
fi

echo "🏗️ A buildar e lançar contentores (isto garante que as dependências internas estão presentes)..."
# Usamos --build para garantir que a node_modules interna do contentor é criada corretamente
docker compose up -d --build

echo "⏳ A aguardar que o FrontEnd inicie (Next.js)..."
# Pequena espera para o Next.js começar a compilar
sleep 5

echo "✅ Ambiente de Desenvolvimento ativo!"
echo "🔗 Next.js: http://localhost:3000 (Modo Dev)"
echo "🔗 Moodle: http://localhost:8080 (Bind Mount ativo)"
echo ""
echo "Dica: As alterações no teu código local serão refletidas automaticamente."
echo "Dica: Use 'docker compose logs -f frontend' para ver a compilação em tempo real."
