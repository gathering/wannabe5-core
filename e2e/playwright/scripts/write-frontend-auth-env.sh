#!/usr/bin/env bash
# Write .env.frontend for e2e/playwright compose (AUTH_PUBLIC_KEY for Next).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
OUT="${1:-$(cd "$(dirname "$0")/.." && pwd)/.env.frontend}"

python3 - <<PY
from pathlib import Path
import json, re
root = Path(r'''$ROOT''')
out = Path(r'''$OUT''')
text = (root / "env/local-stack.env").read_text()
m = re.search(r'AUTH_USER_PUBLIC_KEY="([^"]+)"', text)
if not m:
    raise SystemExit("AUTH_USER_PUBLIC_KEY missing in env/local-stack.env")
pem = m.group(1).encode().decode("unicode_escape")
auth = json.dumps({"key": pem})
# Docker Compose env_file: unquoted value; JSON uses double quotes → wrap in single quotes.
out.write_text(
    "# Generated for e2e/playwright compose frontend\\n"
    f"AUTH_PUBLIC_KEY='{auth}'\\n"
)
print(f"Wrote {out}")
PY
