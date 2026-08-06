#!/bin/sh
set -e

echo "=== ACETEL TMS Entrypoint ==="

# ── Storage directories ──────────────────────────────────────────
mkdir -p storage/framework/views \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/logs \
         bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# ── Preserve .env ────────────────────────────────────────────────
touch .env

# ── Clear cached bootstrap files ────────────────────────────────
rm -f bootstrap/cache/config.php \
      bootstrap/cache/routes.php \
      bootstrap/cache/services.php \
      bootstrap/cache/packages.php 2>/dev/null || true

# ── APP_KEY diagnostics ─────────────────────────────────────────
if [ -n "$APP_KEY" ]; then
    echo "APP_KEY: found (${#APP_KEY} chars)"
else
    echo "WARNING: APP_KEY missing — generating…"
    php artisan key:generate --force
fi

# ── Artisan clears ───────────────────────────────────────────────
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true

# ── Migrations ───────────────────────────────────────────────────
echo "=== Running migrations ==="
php artisan migrate --force || echo "WARN: Migration failed — check DB connection"

# ── Production caching ───────────────────────────────────────────
echo "=== Caching config and routes ==="
php artisan config:cache || echo "WARN: Config cache failed"
php artisan route:cache  || echo "WARN: Route cache failed"

# ── Test whether Redis is actually reachable ─────────────────────
echo "=== Testing Redis connection ==="
php -r "
try {
    \$redis = new Predis\Client([
        'host'     => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port'     => intval(getenv('REDIS_PORT') ?: 6379),
        'password' => (getenv('REDIS_PASSWORD') && getenv('REDIS_PASSWORD') !== 'null') ? getenv('REDIS_PASSWORD') : null,
        'scheme'   => (getenv('REDIS_TLS') === 'true') ? 'tls' : 'tcp',
    ]);
    \$redis->ping();
    echo 'Redis: CONNECTED' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Redis: UNAVAILABLE — using file/array cache fallback' . PHP_EOL;
}
" 2>/dev/null || echo "Redis test skipped (predis not in path context)"

# ── Choose the best available server ─────────────────────────────
# Prefer FrankenPHP > php-fpm+nginx > artisan serve (dev only)
echo "=== Starting application server ==="

if command -v frankenphp > /dev/null 2>&1; then
    echo "Server: FrankenPHP (production)"
    exec frankenphp run --config /etc/caddy/Caddyfile

elif command -v nginx > /dev/null 2>&1 && command -v php-fpm > /dev/null 2>&1; then
    echo "Server: php-fpm + nginx (production)"
    php-fpm -D
    exec nginx -g "daemon off;"

else
    # Fallback: php artisan serve with multiple workers via pcntl fork
    echo "Server: artisan serve (development fallback — consider upgrading to FrankenPHP)"
    exec php artisan serve --host=0.0.0.0 --port=8000
fi
