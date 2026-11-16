#!/bin/sh
# Entrypoint: готовит writable-каталоги Laravel и запускает переданную команду.
# Нужен из-за bind-mount: на Windows файлы монтируются как root:root, и
# php-fpm (www-data) не может писать в storage/ и bootstrap/cache.
set -e

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# На Linux меняем владельца; на Windows-bind chown не действует, но chmod — да.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

exec "$@"
