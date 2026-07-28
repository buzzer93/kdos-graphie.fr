#!/usr/bin/env bash
set -Eeuo pipefail

COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$PROJECT_DIR"

echo "[1/6] git pull --ff-only"
git pull --ff-only

echo "[2/6] Build and restart app container (prod target, no Xdebug)"
$COMPOSE build app
$COMPOSE up -d app

echo "[3/6] Composer install (no dev)"
$COMPOSE exec -T app composer install --no-dev --optimize-autoloader --no-scripts

echo "[4/6] Install importmap packages and compile assets"
$COMPOSE exec -T app php bin/console importmap:install
$COMPOSE exec -T app php bin/console asset-map:compile

echo "[5/6] Doctrine migrations"
$COMPOSE exec -T app php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "[6/6] Clear and warm up cache"
$COMPOSE exec -T app php bin/console cache:clear
$COMPOSE exec -T app php bin/console cache:warmup

echo "Done. VPS update completed."
