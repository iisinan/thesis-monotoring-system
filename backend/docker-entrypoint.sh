#!/bin/sh
set -e

echo "=== Docker Entrypoint Starting ==="
export APP_DEBUG=true

# Ensure logs and app storage exist and are writable
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Touch .env to ensure it exists so key:generate won't crash if it runs
touch .env


# NUCLEAR CACHE CLEAR: Manually delete cache files before Laravel starts
echo "=== Nuking Cache Files ==="
rm -f bootstrap/cache/config.php bootstrap/cache/routes.php bootstrap/cache/services.php bootstrap/cache/packages.php || true

# Print Key Diagnostic (Sanitized)
if [ -n "$APP_KEY" ]; then
    echo "DEBUG: APP_KEY found in ENV. Length: ${#APP_KEY}"
    case "$APP_KEY" in
        base64:*) echo "DEBUG: Key format looks correct (base64 prefix found)" ;;
        *) echo "WARNING: APP_KEY is missing 'base64:' prefix!" ;;
    esac
else
    echo "DEBUG: APP_KEY is MISSING in ENV."
fi

# Clear all cached configurations via Artisan
echo "=== Clearing Caches via Artisan ==="
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

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

echo "=== Starting Server on Port 8000 ==="
exec php artisan serve --host=0.0.0.0 --port=8000
