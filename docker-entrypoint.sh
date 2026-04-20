#!/bin/sh
set -e

echo "=== Docker Entrypoint Starting ==="
echo "APP_ENV: ${APP_ENV}"
echo "DB_CONNECTION: ${DB_CONNECTION}"
echo "LOG_CHANNEL: ${LOG_CHANNEL}"

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set! Generating a temporary one..."
    php artisan key:generate --force
fi

# Ensure required directories exist
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs
chmod -R 775 storage bootstrap/cache

# Run database migrations
echo "=== Running migrations ==="
php artisan migrate --force || { echo "MIGRATION FAILED"; exit 1; }

# Cache config/routes for performance (skip view cache — views are in the image)
echo "=== Caching config and routes ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache || true

echo "=== Starting server on port ${PORT:-8000} ==="
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
