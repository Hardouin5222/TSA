#!/usr/bin/env bash
set -euo pipefail

docker exec -i tsa-postgres psql -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" \
  < services/user-service/migrations/0001_init_auth_foundation.sql
