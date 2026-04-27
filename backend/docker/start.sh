#!/bin/bash
set -e

echo "=== HelpDesk Starting ==="

# Wait for MySQL (max 60 seconds)
echo "Waiting for database connection..."
MAX_TRIES=30
COUNT=0
until php -r "new PDO('mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-helpdesk}', '${DB_USERNAME:-root}', '${DB_PASSWORD:-}');" 2>/dev/null || [ $COUNT -eq $MAX_TRIES ]; do
  COUNT=$((COUNT+1))
  echo "  DB not ready yet ($COUNT/$MAX_TRIES)..."
  sleep 2
done

echo "Running migrations..."
php artisan migrate --force --no-interaction

echo "Seeding initial data..."
php artisan db:seed --force --no-interaction

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

echo "=== Starting services ==="
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
