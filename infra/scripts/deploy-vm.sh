#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/tsa}"
BRANCH="${BRANCH:-main}"

if [ ! -d "$APP_DIR/.git" ]; then
  echo "Repo bulunamadi: $APP_DIR"
  echo "Once repo'yu bu dizine clone et."
  exit 1
fi

cd "$APP_DIR"
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"
docker compose -f docker-compose.server.yml up -d --build
docker compose -f docker-compose.server.yml restart nginx
docker compose -f docker-compose.server.yml ps
echo "Healthcheck:"
curl --fail --silent http://localhost/api/health/live || true
