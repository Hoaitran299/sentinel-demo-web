# Laravel runtime cho local/demo. Source được bind-mount từ host, image chỉ chứa toolchain.
FROM node:22-bookworm-slim AS node

FROM php:8.3-cli-bookworm

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip intl bcmath pcntl \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && git config --system --add safe.directory '*'

COPY entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

WORKDIR /var/www/html
EXPOSE 8000
ENTRYPOINT ["app-entrypoint"]
# Không dùng `artisan serve`: lệnh đó xoá env của container (DB_HOST...) khỏi process con.
# server.php coi thư mục hiện tại là public path nên phải chạy từ public/.
CMD ["sh", "-c", "cd public && exec php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
