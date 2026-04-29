#!/bin/bash
set -e

echo "=== PrimeAxis CRM Starting ==="

# Wait for MySQL (max 60 seconds)
echo "Waiting for database connection..."
MAX_TRIES=30
COUNT=0
until php -r "new PDO('mysql:host=${DB_HOST:-mysql.railway.internal};port=${DB_PORT:-3306}', '${DB_USERNAME:-root}', '${DB_PASSWORD:-}');" 2>/dev/null || [ $COUNT -eq $MAX_TRIES ]; do
  COUNT=$((COUNT+1))
  echo "  DB not ready yet ($COUNT/$MAX_TRIES)..."
  sleep 2
done

echo "Ensuring database exists..."
php -r "
\$pdo = new PDO('mysql:host=${DB_HOST:-mysql.railway.internal};port=${DB_PORT:-3306}', '${DB_USERNAME:-root}', '${DB_PASSWORD:-}');
\$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-primeaxis}\`');
echo 'Database ready.' . PHP_EOL;
"

echo "Running migrations..."
php artisan migrate --force --no-interaction

echo "Seeding initial data..."
php artisan db:seed --force --no-interaction

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache || echo "  [warn] route:cache failed, running without route cache"
php artisan view:cache || true
php artisan storage:link || true

echo "=== Starting services ==="
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
