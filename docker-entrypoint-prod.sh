#!/bin/sh
set -e

cd /app

mkdir -p bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    database \
    public/build

# Cache dari stack dev bisa menyebut provider dev (mis. Laravel Boost) yang
# tidak ada pada instalasi --no-dev. Buang dulu sebelum Laravel di-boot.
rm -f bootstrap/cache/*.php 2>/dev/null || true

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

if [ ! -f vendor/autoload.php ]; then
    echo "[prod] Installing production dependencies..."
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
fi

if [ ! -x node_modules/.bin/vite ]; then
    echo "[prod] Installing frontend dependencies..."
    npm ci --include=dev --no-audit --no-fund
fi

# Pastikan Laravel memakai manifest hasil build, bukan Vite dev server.
rm -f public/hot

if [ ! -f public/build/manifest.json ]; then
    echo "[prod] Building production assets..."
    npm run build
fi

php artisan package:discover --force >/dev/null 2>&1 || true

echo "[prod] Waiting for MySQL at ${DB_HOST:-db}:${DB_PORT:-3306}..."
attempt=0
until php -r '
    $dsn = "mysql:host=" . (getenv("DB_HOST") ?: "db") . ";port=" . (getenv("DB_PORT") ?: "3306") . ";dbname=" . (getenv("DB_DATABASE") ?: "cbt");
    try {
        new PDO($dsn, getenv("DB_USERNAME") ?: "cbt", getenv("DB_PASSWORD") ?: "secret");
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "[prod] MySQL is not reachable after 120 seconds."
        exit 1
    fi
    sleep 2
done

php artisan migrate --force

user_count=$(php -r '
    $dsn = "mysql:host=" . (getenv("DB_HOST") ?: "db") . ";port=" . (getenv("DB_PORT") ?: "3306") . ";dbname=" . (getenv("DB_DATABASE") ?: "cbt");
    try {
        $pdo = new PDO($dsn, getenv("DB_USERNAME") ?: "cbt", getenv("DB_PASSWORD") ?: "secret");
        echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (Throwable $e) {
        echo 0;
    }
')

if [ "$user_count" = "0" ]; then
    echo "[prod] Database kosong, menjalankan seeder..."
    php artisan db:seed --force
fi

# Symlink relatif agar bisa diikuti nginx (mount storage terpisah dari /app).
rm -f /app/public/storage
ln -s ../storage/app/public /app/public/storage

# Folder runtime backup tidak ikut ter-commit; pastikan selalu ada.
mkdir -p /app/storage/app/backup-temp /app/storage/app/private/backups

php artisan optimize:clear >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache >/dev/null 2>&1 || true
php artisan view:cache >/dev/null 2>&1 || true
php artisan event:cache >/dev/null 2>&1 || true

# PHP-FPM berjalan sebagai www-data, sedangkan entrypoint ini berjalan sebagai root.
# Samakan ownership setelah semua artisan selesai menulis cache/log.
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R ug+rwX /app/storage /app/bootstrap/cache

echo "[prod] Ready. Starting PHP-FPM and queue worker..."
exec "$@"
