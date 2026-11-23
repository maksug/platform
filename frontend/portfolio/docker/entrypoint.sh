#!/bin/sh
# Entrypoint: держит node_modules в именованном томе синхронно с lock-файлом.
#
# Исходники приходят bind-mount'ом, а зависимости живут в Linux-томе, поэтому
# после правки package.json в томе остаётся старый набор пакетов. Сравниваем
# md5 lock-файла с контрольной суммой, записанной при установке.
set -e

LOCK_SUM="$(md5sum package-lock.json | cut -d' ' -f1)"
STAMP="node_modules/.lock-sum"

if [ ! -d node_modules ] || [ "$(cat "$STAMP" 2>/dev/null)" != "$LOCK_SUM" ]; then
    echo "entrypoint: зависимости не совпадают с package-lock.json — npm ci"
    npm ci --no-audit --no-fund
    echo "$LOCK_SUM" > "$STAMP"
fi

exec "$@"
