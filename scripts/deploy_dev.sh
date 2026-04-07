#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519_codex_nopass}"
SSH_USER="${SSH_USER:-sc1mcxk1700}"
SSH_HOST="${SSH_HOST:-sobek.o2switch.net}"
REMOTE_DIR="${REMOTE_DIR:-/home/sc1mcxk1700/dev.galette.belhache.net}"
REMOTE_APP_ENV="${REMOTE_APP_ENV:-dev}"

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-sc1mcxk1700_galette_dev}"
DB_USER="${DB_USER:-sc1mcxk1700_galette_dev_user}"
DB_PASSWORD="${DB_PASSWORD:-}"
RUN_SEED="${RUN_SEED:-0}"

[ -n "$DB_PASSWORD" ] || { echo "DB_PASSWORD is required"; exit 1; }

echo "Syncing project to $SSH_USER@$SSH_HOST:$REMOTE_DIR"
rsync -az --delete \
  --exclude=.git/ \
  --exclude=config/secrets.local.php \
  -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=accept-new" \
  "$PROJECT_ROOT"/ "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

echo "Writing remote secrets file"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST" "
  mkdir -p '$REMOTE_DIR/config' &&
  cat > '$REMOTE_DIR/config/secrets.local.php' <<'PHP'
<?php
return [
    'DB_HOST' => '$DB_HOST',
    'DB_PORT' => '$DB_PORT',
    'DB_NAME' => '$DB_NAME',
    'DB_USER' => '$DB_USER',
    'DB_PASSWORD' => '$DB_PASSWORD',
];
PHP
"

echo "Ensuring schema on remote database"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST" "
  if ! mysql -h '$DB_HOST' -P '$DB_PORT' -u '$DB_USER' -p'$DB_PASSWORD' '$DB_NAME' -e 'SELECT 1 FROM users LIMIT 1' >/dev/null 2>&1; then
    mysql -h '$DB_HOST' -P '$DB_PORT' -u '$DB_USER' -p'$DB_PASSWORD' '$DB_NAME' < '$REMOTE_DIR/db/schema.sql'
  fi
"

if [ "$RUN_SEED" = "1" ]; then
  echo "Running demo seed on remote database"
  ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST" "
    APP_ENV='$REMOTE_APP_ENV' php '$REMOTE_DIR/scripts/seed_demo.php'
  "
else
  echo "Skipping demo seed (set RUN_SEED=1 to execute it manually during deployment)"
fi

echo "Deployment to dev completed."
