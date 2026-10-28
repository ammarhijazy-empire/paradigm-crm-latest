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
  cp .env.example .env
  echo "!! Created $DEST/.env from the example. Open it in File Manager, fill DB_* / MAIL_* (and AI_* if ready), then deploy again."; exit 1
fi
if ! grep -qE '^DB_DATABASE=.+' .env; then
  echo "!! DB_DATABASE is empty in $DEST/.env — fill the database lines, then deploy again."; exit 1
fi

# Composer: use the server's if there is one, otherwise download composer.phar once (signature checked). No Terminal needed.
export COMPOSER_HOME="${COMPOSER_HOME:-$HOME/.composer}"
if command -v composer >/dev/null 2>&1; then COMPOSER="composer"
elif [ -x /opt/cpanel/composer/bin/composer ]; then COMPOSER="/opt/cpanel/composer/bin/composer"
else
  if [ ! -f "$HOME/composer.phar" ]; then
    echo "Downloading composer.phar"
    EXPECTED="$("$PHP_BIN" -r 'echo trim(file_get_contents("https://composer.github.io/installer.sig"));')"
    "$PHP_BIN" -r 'copy("https://getcomposer.org/installer", getenv("HOME")."/composer-setup.php");'
    ACTUAL="$("$PHP_BIN" -r 'echo hash_file("sha384", getenv("HOME")."/composer-setup.php");')"
    if [ "$EXPECTED" != "$ACTUAL" ]; then rm -f "$HOME/composer-setup.php"; echo "!! composer installer signature mismatch"; exit 1; fi
    "$PHP_BIN" "$HOME/composer-setup.php" --quiet --install-dir="$HOME" --filename=composer.phar
    rm -f "$HOME/composer-setup.php"
  fi
  COMPOSER="$PHP_BIN -d memory_limit=-1 $HOME/composer.phar"
fi
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

"$PHP_BIN" artisan optimize:clear
# First deploy only: app key and browser-push keys are generated straight into .env
grep -qE '^APP_KEY=base64:' .env || "$PHP_BIN" artisan key:generate --force
grep -qE '^VAPID_PUBLIC_KEY=.+' .env || "$PHP_BIN" artisan webpush:vapid --force
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
