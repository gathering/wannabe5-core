#!/usr/bin/env bash
set -euo pipefail

php artisan scramble:analyze
php artisan scramble:export --path=openapi-ci.json
diff openapi.json openapi-ci.json
rm -f openapi-ci.json

echo "OpenAPI check passed."

