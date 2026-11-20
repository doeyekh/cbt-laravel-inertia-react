# syntax=docker/dockerfile:1

# Ambil runtime Node dan Composer dari image resmi, lalu salin ke image PHP.
FROM node:22-bookworm-slim AS node
FROM composer:2 AS composer

FROM php:8.3-cli-bookworm AS app

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    NODE_ENV=development

# Ekstensi PHP yang dibutuhkan Laravel, dompdf, dan maatwebsite/excel.
# pdo_sqlite, sqlite3, dom, xml, curl, fileinfo sudah bawaan image/php:8.3-cli.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

WORKDIR /app

COPY . .

RUN cp .env.example .env \
    && composer install --prefer-dist --no-interaction \
    && npm ci \
    && php artisan key:generate --force

EXPOSE 8000 5173

ENTRYPOINT ["sh", "/app/docker-entrypoint.sh"]
CMD ["sh", "-c", "./node_modules/.bin/concurrently -k -n server,queue,vite -c '#93c5fd,#c4b5fd,#fdba74' \"php artisan serve --host=0.0.0.0 --port=8000\" \"php artisan queue:listen --tries=1\" \"npm run dev -- --host 0.0.0.0 --port 5173\""]
