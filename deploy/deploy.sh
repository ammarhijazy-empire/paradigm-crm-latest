#!/bin/bash
# Paradigm Cargo CRM — deploy from the cPanel repository clone into the live folder.
# Usage: deploy/deploy.sh /home/USER/crm      (PHP_BIN can be overridden, e.g. PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php)
set -euo pipefail
SRC="$(cd "$(dirname "$0")/.." && pwd)"
DEST="${1:-$HOME/crm}"
PHP_BIN="${PHP_BIN:-$(command -v ea-php83 || command -v php)}"
LOG="$HOME/crm-deploy.log"
exec >>"$LOG" 2>&1
echo "=== deploy $(date '+%F %T') from $SRC to $DEST ==="

command -v rsync >/dev/null || { echo "!! rsync is not installed on this server — ask the host to enable it"; exit 1; }
mkdir -p "$DEST"
# Code: replaced on each deploy. Never touched: .env, storage (uploads, logs), vendor (rebuilt below).
rsync -a --delete \
  --exclude '.git' --exclude '.env' --exclude '/storage' --exclude '/vendor' \
  --exclude '/prototype' --exclude '/node_modules' --exclude '/tests' \
  "$SRC"/ "$DEST"/
# First deploy: create storage tree (existing files are kept)
rsync -a --ignore-existing "$SRC"/storage/ "$DEST"/storage/
mkdir -p "$DEST"/storage/app/private "$DEST"/storage/framework/{cache/data,sessions,views} "$DEST"/storage/logs "$DEST"/bootstrap/cache
chmod -R ug+rwX "$DEST"/storage "$DEST"/bootstrap/cache

cd "$DEST"
if [ ! -f .env ]; then
  echo "!! $DEST/.env is missing — copy .env.example to .env, fill it, then deploy again."; exit 1
fi
if command -v composer >/dev/null 2>&1; then COMPOSER="composer"; else COMPOSER="$PHP_BIN $HOME/composer.phar"; fi
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

"$PHP_BIN" artisan down --retry=30 || true
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --force   # reference data only (roles, ports, settings) — safe to rerun
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan up
echo "=== done $(date '+%F %T') ==="
