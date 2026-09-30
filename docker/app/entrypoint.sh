#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

# node_modules là named volume (binary native của macOS không chạy được trong Linux).
if [ ! -x node_modules/.bin/vite ]; then
    npm ci --no-audit --no-fund
fi

if [ ! -f public/build/manifest.json ]; then
    npm run build
fi

if [ -f public/hot ]; then
    echo "WARNING: public/hot tồn tại -> Laravel sẽ load asset từ Vite dev server. Xoá file này nếu không chạy 'npm run dev'." >&2
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
