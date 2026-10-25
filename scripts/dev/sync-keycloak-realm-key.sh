#!/usr/bin/env bash
# Pull Keycloak realm public key into core .env after Keycloak is up.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
KEYCLOAK_URL="${KEYCLOAK_BASE_URL:-http://keycloak.wannabe5.gathering.org:8080}"
REALM="${KEYCLOAK_REALM:-wannabe}"
TARGET="${1:-$ROOT/.env}"

PUBLIC_KEY="$(curl -fsS "$KEYCLOAK_URL/realms/$REALM" | python3 -c 'import sys,json; print(json.load(sys.stdin)["public_key"])')"

if [[ -z "$PUBLIC_KEY" ]]; then
  echo "Could not read realm public key from $KEYCLOAK_URL/realms/$REALM" >&2
  exit 1
fi

python3 - "$TARGET" "$PUBLIC_KEY" <<'PY'
from pathlib import Path
import sys
target = Path(sys.argv[1])
key = sys.argv[2]
line = f"KEYCLOAK_REALM_PUBLIC_KEY={key}"
if not target.exists():
    target.write_text(line + "\n")
else:
    text = target.read_text()
    import re
    if re.search(r'^KEYCLOAK_REALM_PUBLIC_KEY=', text, re.M):
        text = re.sub(r'^KEYCLOAK_REALM_PUBLIC_KEY=.*$', line, text, count=1, flags=re.M)
    else:
        text = text.rstrip() + "\n" + line + "\n"
    target.write_text(text)
print(f"Updated KEYCLOAK_REALM_PUBLIC_KEY in {target}")
PY
