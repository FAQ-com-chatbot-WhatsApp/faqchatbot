#!/bin/bash

echo "====================================="
echo " FAQ CHATBOT - INICIALIZADOR RÁPIDO"
echo "====================================="
echo

# Verificar se Docker está rodando
if ! docker version >/dev/null 2>&1; then
    echo "[ERRO] Docker não está rodando!"
    echo "   Por favor, inicie o Docker Desktop e tente novamente."
    exit 1
fi

echo "[OK] Docker detectado"
echo

# Navegar para diretório do projeto
cd "$(dirname "$0")"

echo "Diretório: $(pwd)"
echo

# Verificar status atual
echo "Verificando status dos containers..."
CONTAINERS_STATUS=$(docker-compose ps -q)

if [ -n "$CONTAINERS_STATUS" ]; then
    echo "[INFO] Containers existentes encontrados - iniciando rapidamente..."
    echo "       (Para recriar tudo use: docker-compose down -v && docker-compose up -d)"
else
    echo "[INFO] Primeira execução - criando containers e importando dados..."
    echo "       (Isso pode levar 2-3 minutos)"
fi

docker-compose ps

echo
echo "Iniciando aplicação FAQ Chatbot..."
docker-compose up -d

echo
echo "Aguardando inicialização completa..."
sleep 10

echo
echo "Testando conexões..."

# Testar site
if curl -s -I http://localhost:8080 | grep -q "HTTP"; then
    echo "[OK] Site: http://localhost:8080"
else
    echo "[ERRO] Site não respondeu"
fi

# Testar phpMyAdmin
if curl -s -I http://localhost:8081 | grep -q "200 OK"; then
    echo "[OK] phpMyAdmin: http://localhost:8081"
else
    echo "[ERRO] phpMyAdmin não respondeu"
fi

echo
echo "====================================="
echo " APLICAÇÃO FAQ CHATBOT INICIADA!"
echo "====================================="
echo
echo "Acessos disponíveis:"
echo " - Site FAQ: http://localhost:8080"
echo " - phpMyAdmin: http://localhost:8081"
echo " - Admin Joomla: http://localhost:8080/administrator"
echo