#!/usr/bin/env bash
set -euo pipefail

# Keep server URL stable regardless of local APP_URL (e.g. wannabe5.gathering.org).
export APP_URL="${APP_URL_OPENAPI:-http://localhost:8000}"

php artisan scramble:analyze
php artisan scramble:export --path=openapi-ci.json
diff openapi.json openapi-ci.json
rm -f openapi-ci.json

echo "OpenAPI check passed."

