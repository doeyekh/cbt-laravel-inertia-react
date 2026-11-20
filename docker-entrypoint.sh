#!/bin/sh
set -e

cd /app

# Pastikan direktori yang bisa ditulis tersedia walaupun belum ada di clone.
mkdir -p bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    database

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# Tunggu MySQL siap. Compose healthcheck sudah menunggu, ini pengaman tambahan.
echo "[entrypoint] Waiting for MySQL at ${DB_HOST:-db}:${DB_PORT:-3306}..."
attempt=0
until php -r '
    $dsn = "mysql:host=" . (getenv("DB_HOST") ?: "db") . ";port=" . (getenv("DB_PORT") ?: "3306");
    try {
        new PDO($dsn, getenv("DB_USERNAME") ?: "cbt", getenv("DB_PASSWORD") ?: "secret");
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "[entrypoint] MySQL is not reachable after 120 seconds."
        exit 1
    fi
    sleep 2
done
echo "[entrypoint] MySQL is reachable."

# Marker khusus MySQL agar seeder tidak dijalankan ulang setiap restart.
if [ ! -f database/.docker-bootstrapped-mysql ]; then
    echo "[entrypoint] Running migrate:fresh --seed..."
    php artisan migrate:fresh --seed --force
    : > database/.docker-bootstrapped-mysql
else
    php artisan migrate --force
fi

php artisan optimize:clear >/dev/null 2>&1 || true

exec "$@"
