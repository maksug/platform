# syntax=docker/dockerfile:1

ARG NODE_VERSION=22
FROM node:${NODE_VERSION}-alpine

LABEL maintainer="dev"

# tini — корректная обработка сигналов: npm не пробрасывает SIGTERM дочернему
# процессу vite, контейнер иначе останавливается только по timeout.
RUN apk add --no-cache tini

WORKDIR /app

# Зависимости — отдельным слоем: кэш сбрасывается только при изменении lock.
# node_modules при bind-mount с Windows работает медленно и отдаётся как
# root:root, поэтому в рантайме он лежит в именованном томе, который
# инициализируется содержимым образа.
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund \
    && md5sum package-lock.json | cut -d' ' -f1 > node_modules/.lock-sum

# Entrypoint сверяет контрольную сумму lock с установленными зависимостями.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY . .

EXPOSE 5173

ENTRYPOINT ["tini", "--", "/usr/local/bin/entrypoint.sh"]
CMD ["npm", "run", "dev"]
