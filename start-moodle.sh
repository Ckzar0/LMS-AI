#!/bin/bash

# Script de Arranque Seguro para o Ecossistema LMS AI

echo "🚀 Iniciando o Moodle Docker (LMS AI)..."

# Entrar na pasta do moodle
cd moodle-stable

# 1. Carregar variáveis de ambiente do ficheiro .env se existir
if [ -f .env ]; then
    echo "📄 Carregando configurações de .env..."
    # Exporta as variáveis ignorando comentários
    export $(grep -v '^#' .env | xargs)
fi

# 2. Garantir limpeza de redes orfãs e estados anteriores
./bin/moodle-docker-compose down --remove-orphans

# 3. Iniciar os contentores
./bin/moodle-docker-compose up -d

# 4. Aguardar o Banco de Dados (CRÍTICO)
echo "⏳ Aguardando que a base de dados aceite ligações..."
./bin/moodle-docker-wait-for-db

# Voltar à raiz
cd ..

echo "✅ Moodle pronto em http://localhost:8080"
echo "👉 Para iniciar o FrontEnd: cd FrontEnd && npm run dev"
