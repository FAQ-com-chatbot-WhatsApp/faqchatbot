#!/bin/bash
echo "=========================================="
echo "       Iniciando Boot do Sistema Go"
echo "=========================================="

cd "$(dirname "$0")"

if [ ! -f "back/.env" ]; then
    echo "[INFO] Criando arquivo de configuracao (back/.env)..."
    cp back/.env.example back/.env
    echo "[INFO] back/.env criado com sucesso."
else
    echo "[INFO] O arquivo de configuracao (back/.env) ja existe."
fi

echo "[INFO] Verificando Docker no sistema..."
if ! command -v docker &> /dev/null; then
    echo "[ERRO] Docker nao encontrado. Por favor, instale o Docker."
    exit 1
fi

echo "[INFO] Iniciando os containers (isso pode levar alguns minutos na primeira vez)..."
docker compose up -d --build

echo "================================================================="
echo "[SUCESSO] O sistema foi iniciado e esta rodando em segundo plano!"
echo ""
echo "Painel de Controle (Frontend): http://localhost:3000"
echo ""
echo "Usuario Padrao (se banco virgem): admin@admin.com"
echo "Senha Padrao: admin"
echo ""
echo "Por favor, acesse o painel e configure a IA e o WhatsApp via UI!"
echo "================================================================="
