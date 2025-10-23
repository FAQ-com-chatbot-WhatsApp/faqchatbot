#!/bin/bash

# Aguardar banco de dados estar pronto
echo "Aguardando banco de dados..."
sleep 10

# Configurar permissões essenciais (sem recursivo para melhor performance no WSL2)
echo "Configurando permissões essenciais..."

# Criar diretórios necessários
echo "Criando diretórios necessários..."
mkdir -p /var/www/html/administrator/logs
mkdir -p /var/www/html/tmp
mkdir -p /var/www/html/cache
chmod 777 /var/www/html/administrator/logs
chmod 777 /var/www/html/tmp
chmod 777 /var/www/html/cache

# Copiar configuração se não existir
echo "Verificando configuração..."
if [ ! -f /var/www/html/configuration.php ]; then
    if [ -f "/var/www/html/configuration - example.php" ]; then
        cp "/var/www/html/configuration - example.php" /var/www/html/configuration.php
        echo "Configuração copiada do exemplo."
    elif [ -f "/var/www/html/configuration-docker.php" ]; then
        cp "/var/www/html/configuration-docker.php" /var/www/html/configuration.php
        echo "Configuração Docker copiada."
    fi
fi

# Ajustar permissões básicas dos arquivos importantes
echo "Ajustando permissões básicas..."
if [ -f /var/www/html/configuration.php ]; then
    chmod 644 /var/www/html/configuration.php
fi

echo "Iniciando Apache..."
apache2-foreground