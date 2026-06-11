#!/usr/bin/env bash
set -euo pipefail

if [ $# -ne 1 ]; then
  echo "Usage: $0 <email>"
  exit 1
fi

USER_EMAIL="$1"

docker exec -i tsa-postgres psql -v ON_ERROR_STOP=1 -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" <<SQL
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.code = 'admin'
WHERE lower(u.email) = lower('${USER_EMAIL}')
ON CONFLICT ON CONSTRAINT uq_user_roles_user_role DO NOTHING;
SQL

echo "Admin role granted if user exists: ${USER_EMAIL}"
