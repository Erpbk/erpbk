#!/bin/bash
# Non-interactive deployment — run after uploading or overwriting project files.
set -euo pipefail

cd "$(dirname "$0")"

php artisan app:deploy --force --optimize
