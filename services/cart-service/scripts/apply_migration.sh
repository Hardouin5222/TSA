#!/usr/bin/env bash
set -euo pipefail

for migration in services/cart-service/migrations/*.sql; do
  echo "Applying migration: ${migration}"
  docker exec -i tsa-postgres psql -v ON_ERROR_STOP=1 -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" < "${migration}"
done
