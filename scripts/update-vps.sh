#!/usr/bin/env bash
set -Eeuo pipefail

COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$PROJECT_DIR"

echo "[1/7] git pull --ff-only"
git pull --ff-only

echo "[2/7] Build (with fresh base image) and restart containers (prod target, no Xdebug)"
$COMPOSE build --pull app
$COMPOSE up -d --remove-orphans

echo "[3/7] Composer install (no dev)"
$COMPOSE exec -T app composer install --no-dev --optimize-autoloader --no-scripts

echo "[4/7] Install importmap packages and compile assets"
$COMPOSE exec -T app php bin/console importmap:install
$COMPOSE exec -T app php bin/console asset-map:compile

echo "[5/7] Doctrine migrations"
$COMPOSE exec -T app php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "[6/7] Clear and warm up cache"
$COMPOSE exec -T app php bin/console cache:clear
$COMPOSE exec -T app php bin/console cache:warmup

echo "[7/7] Post-deploy checks"
$COMPOSE exec -T app php -r 'echo "PHP ", PHP_VERSION, PHP_EOL;'
$COMPOSE ps --format '{{.Name}}  {{.Status}}  {{.Ports}}'

echo "Done. VPS update completed."