#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "[1/6] Preparando .env"
if [ ! -f "$ROOT_DIR/.env" ]; then
  cp "$ROOT_DIR/.env.example" "$ROOT_DIR/.env"
  echo "  -> .env criado a partir de .env.example"
fi

echo "[2/6] Gerando JWT_SECRET seguro (se placeholder)"
if grep -q 'change_me_generate_in_setup' "$ROOT_DIR/.env"; then
  if command -v openssl >/dev/null 2>&1; then
    SECRET=$(openssl rand -hex 32)
  else
    SECRET=$(uuidgen 2>/dev/null || echo $RANDOM$RANDOM$RANDOM)
  fi
  sed -i.bak "s/JWT_SECRET=.*/JWT_SECRET=${SECRET}/" "$ROOT_DIR/.env" || echo "JWT_SECRET=${SECRET}" >> "$ROOT_DIR/.env"
  echo "  -> JWT_SECRET gerado"
fi

echo "[3/6] Criando diretórios auxiliares"
mkdir -p "$ROOT_DIR/config/jwt" "$ROOT_DIR/storage" "$ROOT_DIR/logs"

echo "[4/6] Instalando dependências Composer"
if command -v composer >/dev/null 2>&1; then
  (cd "$ROOT_DIR" && composer install --no-interaction --prefer-dist --no-scripts)
else
  if [ -f "$ROOT_DIR/composer.phar" ]; then
    php "$ROOT_DIR/composer.phar" install --no-interaction --prefer-dist --no-scripts
  else
    echo "  -> Composer não encontrado (composer ou composer.phar)."
  fi
fi

echo "[5/6] Aplicando schema conversacional (se existir e DB acessível)"
if docker ps --format '{{.Names}}' | grep -q 'whatsbot-db'; then
  if [ -f "$ROOT_DIR/database/schema.sql" ]; then
    docker exec -i whatsbot-db mysql -u bot_user -ppwd123 bot_db < "$ROOT_DIR/database/schema.sql" && echo "  -> schema aplicado"
  fi
  if [ -f "$ROOT_DIR/database/seed_example_flow.sql" ]; then
    docker exec -i whatsbot-db mysql -u bot_user -ppwd123 bot_db < "$ROOT_DIR/database/seed_example_flow.sql" && echo "  -> seed aplicado"
  fi
else
  echo "  -> Container whatsbot-db não está rodando (pulando schema)."
fi

echo "[6/6] Validando autoload"
if [ -f "$ROOT_DIR/vendor/autoload.php" ]; then
  php -r "require 'vendor/autoload.php'; new \App\\Api\\Controllers\\HealthController(); echo 'autoload ok'.PHP_EOL;" || { echo 'Falha no autoload'; exit 1; }
else
  echo "Vendor ausente. Execute composer install após instalar Composer."
fi

echo "Concluído. Para subir containers: docker-compose up -d"
