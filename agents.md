# II API — Agents Reference

## 📦 Стек и версии
- **Framework**: Laravel 13.42 (API-only, `install:api`)
- **Auth**: Laravel Sanctum 4 (Bearer-токены)
- **PHP**: 8.5 (FPM, Alpine)
- **БД**: PostgreSQL 18 (Alpine)
- **Кэш/Очереди**: Redis 8 (Alpine)
- **HTTP-фасад**: Nginx 1.29 (Alpine)
- **Dev-инструменты**: Mailpit (SMTP), Adminer (DB UI)
- **Архитектура**: классический Laravel (Controllers / Models / Services / Actions)
- **Тесты**: Pest 4.7 (поверх PHPUnit 12), `RefreshDatabase`, БД `api_test`
- **Качество кода**: Larastan 3.15 (level 6), Pint 1.32 (preset `laravel`)

## 🗂 Структура монорепо
```
ii/
├── infra/                    # Платформа: сеть, данные, HTTP, dev-инструменты
│   ├── docker-compose.yml    # собирает сервисы через `include:`
│   ├── .env                  # ТОЛЬКО compose (порты, POSTGRES_*)
│   ├── nginx/default.conf    # root = /var/www/core/public
│   ├── postgres/init-db.sql  # расширения + создание api_test
│   └── redis/redis.conf
├── services/
│   └── core/                 # Laravel-сервис (самодостаточная папка)
│       ├── Dockerfile        # образ сервиса
│       ├── compose.yml       # фрагмент: core + core-queue + core-scheduler
│       ├── docker/           # php.ini, fpm-pool.conf, entrypoint.sh
│       ├── .gitignore        # секреты и артефакты сервиса
│       ├── .env              # ТОЛЬКО Laravel (не коммитится)
│       └── app/ ... tests/
├── frontend/                 # резерв под SPA (пока только README)
├── contracts/                # OpenAPI/AsyncAPI — общий артефакт
├── .gitignore                # секреты и артефакты всего монорепо
├── dev.ps1                   # обёртки (из корня, compose в infra/)
└── agents.md
```
Правило: **сервис не импортирует код соседних сервисов**. Тогда `git subtree split --prefix=services/core` даёт готовый репозиторий с историей.

## 🌿 Git-модель: монорепо сейчас, отдельные репозитории потом

- Сейчас **один репозиторий** в корне `ii/`, ветка `main`, первый коммит `chore: initial monorepo import`.
- Каждый каталог верхнего уровня (`infra/`, `services/core/`, `contracts/`, `frontend/`) проектируется как **самодостаточный**: не импортирует код соседей, держит свои зависимости, конфиги и `.gitignore`.
- Вложенные `.git` в подкаталогах **не создавать**: они превращают каталог в gitlink и ломают `subtree split`.

### Процедура выноса каталога

```powershell
# 1. История каталога -> отдельная ветка
git subtree split --prefix=services/core -b core-standalone

# 2. Отдельный репозиторий и перенос истории
git init ..\ii-core
git -C ..\ii-core pull ..\ii core-standalone

# 3. Каталог исключается из монорепо
git rm -r --cached services/core
# в корневом .gitignore раскомментировать /services/core/
```

⚠️ Вынос ломает относительные пути, которые работают сейчас:
`include: ../services/core/compose.yml` в `infra/docker-compose.yml`, bind-mount nginx
`../services/core:/var/www/core` и пути `$CoreEnv` в `dev.ps1`.
Варианты: держать клон на том же месте (`services/core`) либо перевести infra
на внешние переменные. Решение фиксируется отдельным шагом перед выносом.

### Доступ команд

| Кто | Видит | Не видит |
|---|---|---|
| Бэкенд | весь репозиторий | — |
| Фронтенд | `frontend/`, `contracts/` | `services/core/`, `infra/` |
| DevOps | весь репозиторий | — |

Пока репозиторий один, изоляция обеспечивается **правилами доступа и ревью**
(`CODEOWNERS`, защита `main`), а не структурой каталогов. Физическая изоляция
появляется только после выноса `services/core` в отдельный репозиторий.

### Секреты

- `.env`, `.env.testing` не коммитятся: корневой `.gitignore` + `services/core/.gitignore`.
- Шаблоны `.env.example`, `.env.testing.example` — коммитятся.
- Проверка перед коммитом: `git ls-files | findstr /i ".env"` — в выводе только `*.example`.

## 🐳 Docker-архитектура
- **`infra/docker-compose.yml`** — платформа: nginx, pg, redis, mailpit, adminer. Сервисы подключаются через `include: ../services/core/compose.yml`
- **`services/core/compose.yml`** — сервис владеет своим образом, воркерами и томами (`core`, `core-queue`, `core-scheduler` в профиле `workers`)
- **Связь**: `nginx` → `core` (php-fpm:9000) → БД/Redis; порядок старта через healthcheck-цепочки
- **Производительность на Windows**: код — bind-mount, а `vendor/`, `node_modules/`, composer-кэш — именованные Linux-тома (`ii-core-vendor`, `ii-core-node-modules`, `ii-core-composer-cache`)
- **Логи**: rotation 10 МБ × 3 файла на сервис
- **Рестарт**: приложение — `restart: "no"` (падение видно сразу), инфраструктура (pg/redis/nginx) — `unless-stopped`
- **Конфигурация — два файла, не один**: `infra/.env` (compose) и `services/core/.env` (Laravel). Проверка совпадения `DB_*`: `.\dev.ps1 env-check`
- **Обёртки**: `dev.ps1` в корне — `up`, `workers`, `down`, `sh`, `artisan`, `composer`, `test`, `lint`, `pint`, `stan`, `qa`, `env-check`, `migrate`, `fresh`, `seed`, `key`, `rebuild`, `clean`

## 🖥 Фронтенд (зарезервировано)
- `frontend/` — пустая папка под SPA, пока только `README.md` с договорённостями
- Стек не выбран; бэкенд отдаёт чистый REST API, фронт на него не влияет
- Когда появится код: свои `package.json`/`node_modules`/`.env`, Docker-фрагмент `frontend/compose.yml` + строка в `include:` у `infra/docker-compose.yml`
- Типы API — из `contracts/openapi.yaml`, не руками
- Порт `5173` (`VITE_PORT`) зарезервирован в `infra/.env`
- Папка самодостаточна: `git subtree split --prefix=frontend` → отдельный репозиторий
- **Доступ**: фронтенд-команда не получает `services/core/` и `infra/`. Пока это монорепо — изоляция через правила ревью; после выноса `frontend/` в свой репозиторий — физическая

## 🧪 Тесты и качество кода
- **Pest 4.7** — тесты в `tests/`, `RefreshDatabase` включён для `Feature`
- **Отдельная БД `api_test`** на том же PostgreSQL 18 (не sqlite!): тесты ловят pg-специфику (citext, jsonb, регистр, ограничения)
- `.env.testing` (не в git) — тестовое окружение; шаблон `.env.testing.example`
- **Larastan level 6**, пути `app/ database/ routes/`; тесты в анализ не входят (Pest-плагин для PHPStan есть только для Pest 5)
- **Pint** preset `laravel`, конфиг `pint.json`
- **Один прогон всего QA**: `.\dev.ps1 qa` (или `composer qa` = lint + stan + test)

## 🔌 Порты (кто где слушает)
| Сервис | Хост | Доступ |
|---|---|---|
| API (nginx) | `0.0.0.0:8000` | из сети (телефон и т.п.) |
| PostgreSQL | `127.0.0.1:5433` | только с хоста ⚠️ не 5432! |
| Adminer | `127.0.0.1:8080` | только с хоста |
| Mailpit | `127.0.0.1:8025` | только с хоста |
| Redis | не публикуется | `docker compose -f infra/docker-compose.yml exec redis redis-cli` |

⚠️ На машине пользователя стоит **нативный PostgreSQL 17** на порту 5432 (`C:\Program Files\PostgreSQL\17`) — конфликтовать с ним нельзя, контейнерная БД специально на 5433. Внутри docker-сети Laravel ходит на `pg:5432` как обычно.

## 💾 Хранение и сброс данных
- `ii-pgdata` (PG), `ii-redisdata` (Redis) — **named volumes** Docker
- `ii-core-vendor`, `ii-core-node-modules`, `ii-core-composer-cache` — производные артефакты, можно удалять без боли
- Переживают `restart`, `rm`, `up`, `build`, `down` (без `-v`)
- **Полный сброс**: только `.\dev.ps1 clean` (выполняет `down -v`) или ручное `docker volume rm`
- ⚠️ **Ловушка**: изменение `POSTGRES_DB`/`POSTGRES_USER` в `infra/.env` после первой инициализации **не переключит** БД автоматически. Том уже содержит старый кластер. Требуется `clean` или ручное `DROP DATABASE` / `CREATE DATABASE`.

## 🛠 Известные особенности и исправления (Docker/PHP)
| Проблема | Решение |
|---|---|
| `docker-php-ext-install` падает при параллельной сборке нескольких расширений (`cp: can't stat 'modules/*'`) | Вызовы разделены по отдельным `RUN` слоям |
| `apk del` удалял runtime-библиотеки (`libpq`, `libicuio`, `libzip`) | Runtime-пакеты вынесены в отдельный `apk add` до `.build-deps` |
| PostgreSQL 18 сменил схему данных | Том монтируется в `/var/lib/postgresql`, а не `/var/lib/postgresql/data` |
| Healthcheck PG не видит переменные из `env_file` | Использован escape `$$POSTGRES_USER` в `CMD-SHELL` |
| `sockets` не собирался на Alpine 8.5 | Убран из сборки (встроен в PHP 8+ и Laravel не требует) |
| Xdebug включён в образ, но отключён по умолчанию | `XDEBUG_MODE=off` в `.env`; для отладки меняется на `debug` |
| `MissingAppKeyException` при живом `APP_KEY` в `.env` | `.env` **не** передаётся через `env_file` — иначе пустой `APP_KEY` на момент старта контейнера перекрывает файл (Laravel не перезаписывает env). Compose читает `.env` для `${VAR}`, Laravel — сам |
| 500 `tempnam(): file created in the system's temporary directory` | `storage/` и `bootstrap/cache` приходят из bind-mount как `root:root`. Entrypoint (`services/core/docker/entrypoint.sh`) делает `chown`/`chmod` при старте |
| Предупреждение `JIT is incompatible with third-party extensions` | В `php.ini`: `opcache.jit=disable` + `opcache.jit_buffer_size=0` (Xdebug конфликтует с JIT) |
| Larastan ругается на `UserFactory::definition()` | Тип в PHPDoc: `array<model-property<User>, mixed>` (строже, чем `array<string, mixed>`) |
| `pest-plugin-phpstan` не ставится | Существует только для Pest 5; для Pest 4 тесты исключены из PHPStan |
| `docker compose` из корня не находит файл | Compose-файл в `infra/`. Использовать `.\dev.ps1 ...` или `docker compose -f infra/docker-compose.yml ...` |
| Расхождение `DB_*` между `infra/.env` и `services/core/.env` | Даёт `password authentication failed`. Проверка: `.\dev.ps1 env-check` |

## ✅ Текущий статус
- [x] Монорепо разложено: `infra/` + `services/core/` + `contracts/`, фрагмент compose на сервис
- [x] Образ `ii/core:dev` собран (PHP 8.5.12, все необходимые расширения)
- [x] Все сервисы healthy, связность БД/Redis из контейнера `core` подтверждена
- [x] DevOps-ревью внедрено: volumes для vendor/composer-кэша, healthcheck-цепочки, loopback-порты, log rotation, профиль `workers`
- [x] Laravel 13.42 установлен, `install:api` (Sanctum 4), `APP_KEY` сгенерирован
- [x] Миграции применены: `users`, `cache`, `jobs`, `personal_access_tokens`
- [x] `GET /up` → 200, `GET /api/user` без токена → 401
- [x] Инструменты качества: Pest 4.7, Larastan 3.15 (level 6), Pint 1.32; `composer qa` зелёный
- [x] Тесты на PostgreSQL (`api_test`): 4 теста, 7 утверждений
- [x] `dev.ps1` настроен и работает на PowerShell
- [x] Git-репозиторий в корне: ветка `main`, первый коммит `b784454`
- [x] `.gitignore` в корне и в сервисе: `.env`, `.env.testing`, `vendor`, `node_modules` не попадают в индекс

## 🗺 Маршруты и эндпоинты
| Метод | URI | Статус |
|---|---|---|
| GET | `/up` | health-check Laravel (200) |
| GET | `/` | welcome-заглушка (200) |
| GET | `/api/user` | `auth:sanctum`, без токена → 401 |

## ⏳ Следующие шаги
1. Контракт: `contracts/openapi.yaml`, экспорт схемы из Laravel, проверка расхождений в CI, semver и changelog
2. CORS, версионирование `/api/v1`, auth-эндпоинты для фронтенда
3. Фронтовый контур: `frontend/compose.yml`, прокси на API, MSW-моки по контракту
4. CODEOWNERS и защита `main`; remote и первый push
5. Вынос каталогов в отдельные репозитории через `git subtree split` с правкой относительных путей в `infra/`

## 📝 Примечания для агентов
- Не использовать `make` — среда Windows, все обёртки в `dev.ps1`
- Все команды разработчика выполняются внутри контейнера `core` через `docker compose -f infra/docker-compose.yml exec core ...` (или `.\dev.ps1 ...`)
- `.env` не должен коммититься; шаблоны — `infra/.env.example`, `services/core/.env.example`
- Для отладки Xdebug: `XDEBUG_MODE=develop` в `infra/.env`, затем `.\dev.ps1 up`
- Тонкие места: смена имён БД/пользователей после первого запуска, команда `clean` безвозвратно удаляет тома, рассинхрон двух env-файлов
- Git: ветка `main`, репозиторий один (монорепо). Вложенные `.git` в подкаталогах не создавать — это ломает `git subtree split`
- Перед коммитом проверять индекс: `git ls-files | findstr /i ".env"` — только `*.example`
- `.env` и `.env.testing` не коммитятся никогда; шаблоны — `.env.example`, `.env.testing.example`
- При правке `infra/` помнить: пути `../services/core` станут проблемой при выносе сервиса в отдельный репозиторий