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
setenv() { # setenv KEY VALUE  — replace or append a line in .env (value without spaces/quotes)
  if grep -qE "^$1=" .env; then sed -i -E "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
}
if [ ! -f .env ]; then
  cp .env.example .env
  chmod 600 .env
  echo "Created $DEST/.env from the example."
fi
if [ -n "${CRM_URL:-}" ]; then setenv APP_URL "$CRM_URL"; fi

# No database configured yet: create one through cPanel's own API with a random password
# generated here, so no password is ever typed or sent anywhere.
if ! grep -qE '^DB_DATABASE=[^[:space:]#]+' .env; then
  command -v uapi >/dev/null || { echo "!! uapi not available — create the database in cPanel and fill DB_* in $DEST/.env"; exit 1; }
  CP_USER="$(whoami)"; DBN="${CP_USER}_crm"; DBU="${CP_USER}_crm"
  DBP="$("$PHP_BIN" -r 'echo substr(str_replace(["+","/","="],"",base64_encode(random_bytes(48))),0,32);')"
  ok() { grep -q '"status":1' <<<"$1"; }
  R=$(uapi --output=json Mysql create_database name="$DBN"); ok "$R" || grep -q 'already exists' <<<"$R" || { echo "!! create_database: $R"; exit 1; }
  R=$(uapi --output=json Mysql create_user name="$DBU" password="$DBP")
  if ! ok "$R"; then
    grep -q 'already exists' <<<"$R" || { echo "!! create_user: $R"; exit 1; }
    R=$(uapi --output=json Mysql set_password user="$DBU" password="$DBP"); ok "$R" || { echo "!! set_password: $R"; exit 1; }
  fi
  R=$(uapi --output=json Mysql set_privileges_on_database user="$DBU" database="$DBN" privileges='ALL PRIVILEGES'); ok "$R" || { echo "!! privileges: $R"; exit 1; }
  setenv DB_CONNECTION mysql; setenv DB_HOST localhost; setenv DB_DATABASE "$DBN"; setenv DB_USERNAME "$DBU"; setenv DB_PASSWORD "$DBP"
  unset DBP
  echo "Database $DBN and user $DBU created; credentials written to .env"
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

# config:clear only: the cache table may not exist before the first migration
"$PHP_BIN" artisan config:clear
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

# Primary-domain hosting: serve the app from public_html (kept as a backup folder the first time).
if [ "${LINK_PUBLIC_HTML:-0}" = "1" ] && [ "$(readlink -f "$HOME/public_html")" != "$(readlink -f "$DEST/public")" ]; then
  if [ -e "$HOME/public_html" ] && [ ! -L "$HOME/public_html" ]; then mv "$HOME/public_html" "$HOME/public_html.before-crm-$(date +%Y%m%d%H%M%S)"; fi
  rm -f "$HOME/public_html"
  ln -s "$DEST/public" "$HOME/public_html"
  echo "public_html now points to $DEST/public"
fi
echo "=== done $(date '+%F %T') ==="
