#!/usr/bin/env bash

set -Eeuo pipefail

project_root="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$project_root"

timestamp="$(date -u +%Y%m%d-%H%M%S)"
backup_path="storage/app/backups/${timestamp}-pre-deploy.sql"

php artisan database:backup --path="$backup_path"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

maintenance_enabled=false
restore_application() {
    if [[ "$maintenance_enabled" == true ]]; then
        php artisan up
    fi
}
trap restore_application EXIT

php artisan down --retry=60
maintenance_enabled=true
php artisan migrate --force --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan retail:launch-check
php artisan up
maintenance_enabled=false

printf 'Deployment completed. Pre-deploy backup: %s\n' "$backup_path"
