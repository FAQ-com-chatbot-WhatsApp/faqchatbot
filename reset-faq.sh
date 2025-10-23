#!/bin/bash

echo "====================================="
echo " FAQ CHATBOT - RESET COMPLETO"
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

echo "[AVISO] Este comando vai REMOVER TODOS os dados e containers!"
read -p "Tem certeza que deseja continuar? (s/N): " -n 1 -r
echo

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "Operação cancelada."
    exit 1
fi

echo
echo "Parando e removendo containers, volumes e dados..."
docker-compose down -v

echo
echo "Limpando sistema Docker..."
docker system prune -f

echo
echo "Recriando aplicação do zero..."
docker-compose up -d

echo
echo "Aguardando inicialização completa..."
sleep 15

echo
echo "====================================="
echo " RESET COMPLETO FINALIZADO!"
echo "====================================="
echo
echo "Acessos disponíveis:"
echo " - Site FAQ: http://localhost:8080"
echo " - phpMyAdmin: http://localhost:8081"
echo " - Admin Joomla: http://localhost:8080/administrator"
echo