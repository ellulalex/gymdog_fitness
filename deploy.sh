#!/usr/bin/env bash
#
# Gymdog commerce platform — deploy script.
#
# Adapted from the proven Photocart pipeline. Frontend assets are ALWAYS built
# locally and rsynced up: the Hetzner box's npm registry times out (Photocart
# lesson). Everything below the configuration block is server-agnostic — only
# the four variables need filling in when the new box is provisioned.
#
# Usage:  ./deploy.sh [branch]        (defaults to main)
# Config: override any variable via environment, e.g. DEPLOY_HOST=root@1.2.3.4
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration — fill these in when the new Hetzner box is provisioned.
# ---------------------------------------------------------------------------
REMOTE_HOST="${DEPLOY_HOST:-gymdog@46.224.160.95}"
REMOTE_PATH="${DEPLOY_PATH:-/home/gymdog/app}"
SSH_KEY="${DEPLOY_SSH_KEY:-$HOME/.ssh/id_ed25519}"
BRANCH="${1:-main}"
# ---------------------------------------------------------------------------

if [[ "$REMOTE_HOST" == *REPLACE_* || "$REMOTE_PATH" == *REPLACE_* ]]; then
  echo "✋ deploy.sh is not configured yet."
  echo "   Set DEPLOY_HOST and DEPLOY_PATH (env vars or edit the config block)"
  echo "   once the staging/prod server is provisioned."
  exit 1
fi

echo "→ Building assets locally (branch: $BRANCH)"
npm ci
npm run build

echo "→ Pushing $BRANCH"
git push origin "$BRANCH"

echo "→ Syncing built assets to $REMOTE_HOST"
rsync -az --delete -e "ssh -i $SSH_KEY" public/build/ "$REMOTE_HOST:$REMOTE_PATH/public/build/"

echo "→ Running remote release steps"
ssh -i "$SSH_KEY" "$REMOTE_HOST" bash -s <<EOF
  set -euo pipefail
  cd "$REMOTE_PATH"
  git fetch origin
  git checkout "$BRANCH"
  git pull origin "$BRANCH"
  composer install --no-dev --optimize-autoloader --no-interaction
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan filament:optimize
  php artisan horizon:terminate   # systemd respawns Horizon with the new code
EOF

echo "✅ Deployment complete!"

# ---------------------------------------------------------------------------
# First-time setup: run deploy/provision.sh on the box, then follow
# deploy/PROVISIONING.md (deploy key → clone → .env → migrate/seed → SSL).
# Always back up the database before deploying to production.
# ---------------------------------------------------------------------------
