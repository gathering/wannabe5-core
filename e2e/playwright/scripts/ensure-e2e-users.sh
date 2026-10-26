#!/usr/bin/env bash
# Ensure Keycloak has normalbruker with a fixed UUID (matches LocalStackSeeder).
# testbruker comes from realm import. Safe to re-run.
set -euo pipefail

KEYCLOAK_URL="${KEYCLOAK_URL:-http://keycloak.wannabe5.gathering.org:8080}"
REALM="${KEYCLOAK_REALM:-wannabe}"
ADMIN_USER="${KEYCLOAK_ADMIN:-admin}"
ADMIN_PASSWORD="${KEYCLOAK_ADMIN_PASSWORD:-Wannabe5-dev}"

NORMAL_ID="${E2E_USER_ID:-d7a57211-2187-44d2-a9bd-e5deba8fe5ae}"
NORMAL_USERNAME="${E2E_USER_USER:-normalbruker}"
NORMAL_PASSWORD="${E2E_USER_PASSWORD:-normalbruker}"
NORMAL_EMAIL="${E2E_USER_EMAIL:-normalbruker@example.com}"

echo "Ensuring Keycloak user ${NORMAL_USERNAME} (${NORMAL_ID}) in realm ${REALM}…"

token="$(
  curl -fsS -X POST \
    "${KEYCLOAK_URL}/realms/master/protocol/openid-connect/token" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    -d "username=${ADMIN_USER}" \
    -d "password=${ADMIN_PASSWORD}" \
    -d 'grant_type=password' \
    -d 'client_id=admin-cli' \
    | python3 -c 'import sys,json; print(json.load(sys.stdin)["access_token"])'
)"

auth=( -H "Authorization: Bearer ${token}" -H 'Content-Type: application/json' )

# Partial import keeps a stable user id (required so JWT sub matches core User.id).
payload="$(NORMAL_ID="$NORMAL_ID" NORMAL_USERNAME="$NORMAL_USERNAME" NORMAL_PASSWORD="$NORMAL_PASSWORD" NORMAL_EMAIL="$NORMAL_EMAIL" python3 - <<'PY'
import json, os
print(json.dumps({
  "ifResourceExists": "SKIP",
  "users": [{
    "id": os.environ["NORMAL_ID"],
    "username": os.environ["NORMAL_USERNAME"],
    "enabled": True,
    "email": os.environ["NORMAL_EMAIL"],
    "emailVerified": True,
    "firstName": "Normal",
    "lastName": "Bruker",
    "credentials": [{
      "type": "password",
      "value": os.environ["NORMAL_PASSWORD"],
      "temporary": False,
    }],
  }],
}))
PY
)"

curl -fsS -X POST \
  "${KEYCLOAK_URL}/admin/realms/${REALM}/partialImport" \
  "${auth[@]}" \
  -d "$payload" >/dev/null

# Resolve actual id (SKIP may mean a pre-existing user with same username/different id).
existing="$(
  curl -fsS "${KEYCLOAK_URL}/admin/realms/${REALM}/users?username=${NORMAL_USERNAME}&exact=true" \
    "${auth[@]}"
)"
user_id="$(python3 -c 'import sys,json; print(json.load(sys.stdin)[0]["id"])' <<<"$existing")"

if [[ "$user_id" != "$NORMAL_ID" ]]; then
  echo "WARNING: Keycloak id for ${NORMAL_USERNAME} is ${user_id}, expected ${NORMAL_ID}." >&2
  echo "Delete the Keycloak user and re-run, or align LocalStackSeeder / E2E_USER_ID." >&2
fi

curl -fsS -X PUT \
  "${KEYCLOAK_URL}/admin/realms/${REALM}/users/${user_id}/reset-password" \
  "${auth[@]}" \
  -d "{\"type\":\"password\",\"value\":\"${NORMAL_PASSWORD}\",\"temporary\":false}"

echo "Keycloak user ready: ${NORMAL_USERNAME} (id=${user_id})"
