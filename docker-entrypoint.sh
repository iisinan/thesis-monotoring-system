#!/bin/sh
set -e

echo "=== Docker Entrypoint Starting ==="
export APP_DEBUG=true

# Ensure logs and app storage exist and are writable
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Delete .env to ensure Railway variables take priority
rm -f .env || true

# Print Key Diagnostic (Sanitized)
if [ -n "$APP_KEY" ]; then
    echo "DEBUG: APP_KEY found in ENV. Length: ${#APP_KEY}"
else
    echo "DEBUG: APP_KEY is MISSING in ENV."
fi

# Clear all cached configurations
echo "=== Clearing Caches ==="
php artisan config:clear || echo "Config clear skipped"
php artisan route:clear || echo "Route clear skipped"
php artisan view:clear || echo "View clear skipped"

# Generate application key if still not set
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is still not set! Generating a temporary one..."
    php artisan key:generate --force
fi

# Run database migrations
echo "=== Running Migrations ==="
php artisan migrate --force || echo "MIGRATION FAILED - check database connection"

# Optional: Re-cache for production (skip view cache if problematic)
echo "=== Caching Config ==="
php artisan config:cache || echo "Config cache failed"
php artisan route:cache || echo "Route cache failed"

echo "=== Starting Server on Port ${PORT:-8000} ==="
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
