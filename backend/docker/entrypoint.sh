#!/bin/sh
# Prepares the app on container start, then runs the API. Every step is safe to
# repeat, so restarts keep the existing .env, app key and database.
set -e

[ -f .env ] || cp .env.example .env

# The vendor volume outlives image rebuilds, so sync it with composer.lock here.
composer install --no-interaction --no-progress

grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

touch /data/database.sqlite
php artisan migrate --force

# --no-reload is required for PHP_CLI_SERVER_WORKERS to take effect. It means
# .env changes need `docker compose restart api`; code changes apply immediately.
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
