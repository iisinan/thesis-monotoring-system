#!/bin/sh
set -e

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run database migrations (NOT seeders)
php artisan migrate --force

# Cache configuration, routes, and views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache || true

# Start the Laravel development server
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
