# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-alpine

LABEL maintainer="dev"

# Runtime-библиотеки (останутся в образе)
RUN apk add --no-cache \
        bash git curl zip unzip openssh-client \
        icu-libs libpq libzip oniguruma \
        tini

# Build-time зависимости (удаляются после компиляции расширений)
RUN apk add --no-cache --virtual .build-deps \
        icu-dev postgresql-dev libzip-dev oniguruma-dev \
        autoconf g++ make linux-headers

# Встроенные PHP-расширения.
# Устанавливаем раздельно: параллельная сборка нескольких расширений в одном
# docker-php-ext-install даёт гонку в common make-каталоге.
# OPcache уже встроен в базовый образ php:*-fpm-alpine.
RUN docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install bcmath exif pcntl zip \
    && docker-php-ext-install -j"$(nproc)" intl

# PECL-расширения (redis + xdebug)
RUN pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug

# Удаляем только build-deps, runtime библиотеки остаются
RUN apk del --no-network .build-deps \
    && rm -rf /tmp/pear /usr/src/php

WORKDIR /var/www/core

# Копируем php.ini и конфигурацию PHP-FPM
COPY docker/php.ini       /usr/local/etc/php/conf.d/99-app.ini
COPY docker/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-app.conf

# Composer — собираем из официального образа
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MAX_PARALLEL_HTTP=4

# Entrypoint: готовит права на storage/ и bootstrap/cache (bind-mount Windows)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["tini", "--", "/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
