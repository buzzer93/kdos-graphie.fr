#!/usr/bin/env bash
set -Eeuo pipefail

APP_ENV="${APP_ENV:-prod}"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$PROJECT_DIR"

echo "[1/5] git pull --ff-only"
git pull --ff-only

echo "[2/5] Doctrine migrations"
php bin/console doctrine:migrations:migrate --no-interaction --env="$APP_ENV"

echo "[3/5] Build Tailwind"
php bin/console tailwind:build --minify

echo "[4/5] Compile AssetMapper"
php bin/console asset-map:compile

echo "[5/5] Clear cache ($APP_ENV)"
php bin/console cache:clear --env="$APP_ENV"

echo "Done. VPS update completed."