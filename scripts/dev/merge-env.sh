#!/usr/bin/env bash
# Merge KEY=VALUE lines from a source env file into a target .env (overwrite matching keys).
set -euo pipefail

SOURCE="${1:?source env file required}"
TARGET="${2:?target env file required}"

if [[ ! -f "$SOURCE" ]]; then
  echo "Missing source env: $SOURCE" >&2
  exit 1
fi

if [[ ! -f "$TARGET" ]]; then
  cp "$SOURCE" "$TARGET"
  echo "Created $TARGET from $SOURCE"
  exit 0
fi

python3 - "$SOURCE" "$TARGET" <<'PY'
import re, sys
from pathlib import Path

source, target = Path(sys.argv[1]), Path(sys.argv[2])
src_text = source.read_text()
tgt_text = target.read_text()

# Parse KEY=VALUE (supports quoted values spanning one line with \n escapes)
kv = {}
for line in src_text.splitlines():
    if not line.strip() or line.lstrip().startswith('#') or '=' not in line:
        continue
    key, value = line.split('=', 1)
    kv[key.strip()] = value

out_lines = []
seen = set()
for line in tgt_text.splitlines():
    if not line.strip() or line.lstrip().startswith('#') or '=' not in line:
        out_lines.append(line)
        continue
    key = line.split('=', 1)[0].strip()
    if key in kv:
        out_lines.append(f"{key}={kv[key]}")
        seen.add(key)
    else:
        out_lines.append(line)

missing = [k for k in kv if k not in seen]
if missing:
    out_lines.append('')
    out_lines.append('# --- merged from local stack ---')
    for key in missing:
        out_lines.append(f"{key}={kv[key]}")

target.write_text('\n'.join(out_lines) + '\n')
print(f"Merged {len(kv)} keys into {target}")
PY
