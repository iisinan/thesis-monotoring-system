#!/bin/sh
set -e

echo "=== Docker Entrypoint Starting ==="
export APP_DEBUG=true

# Delete .env to ensure Railway variables take priority
rm -f .env

# Print Key Diagnostic (Sanitized)
if [ -n "$APP_KEY" ]; then
    echo "APP_KEY detected. Length: ${#APP_KEY} characters."
else
    echo "APP_KEY is MISSING in environment variables."
fi

# Clear all cached configurations
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate application key if still not set
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is still not set! Generating a temporary one..."
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
