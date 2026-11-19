# Foundation

Фундамент для будущих проектов: готовый dev-контур (Docker + Laravel API +
PostgreSQL + Redis) с контрактами и правилами репозитория. Конкретные
продуктовые домены ещё не выбраны, поэтому название не привязано к стеку —
каждый проект вырастает отсюда копией или `git subtree split`.

- **Стек сервиса**: PHP 8.5, Laravel 13 (API-only), Sanctum 4, PostgreSQL 18, Redis 8.
- **Стек платформы**: Docker Compose, nginx (php-fpm), Mailpit, Adminer.
- **Контракт**: OpenAPI в `contracts/openapi.yaml`.

Документы для агентов и команд: [`agents.md`](agents.md) (корень),
[`services/core/agents.md`](services/core/agents.md),
[`frontend/agents.md`](frontend/agents.md), [`contracts/README.md`](contracts/README.md).

## Требования

- Docker Desktop (Compose v2)
- PowerShell (среда разработки — Windows; `make` не используется)
- Свободные порты `8000`, `5433`, `8080`, `8025`

⚠️ Контейнерная БД на `5433`, а не на `5432`: на хосте может стоять нативный
PostgreSQL.

## Быстрый старт

```powershell
# 1. Файлы окружения (секреты не коммитятся)
Copy-Item infra\.env.example infra\.env
Copy-Item services\core\.env.example services\core\.env

# 2. Поднять стек (сборка образа + миграции на старте)
.\dev.ps1 up

# 3. Проверка
curl http://localhost:8000/up     # 200
```

Первое поднятие занимает несколько минут: `vendor/` ставится composer-ом в
именованный том.

```powershell
.\dev.ps1 env-check   # DB_* в services/core/.env == POSTGRES_* в infra/.env
.\dev.ps1 artisan migrate
.\dev.ps1 qa          # lint + stan + test
```

## Структура

```
foundation/
├── infra/            # платформа: docker-compose.yml, nginx, postgres, redis
├── services/core/    # Laravel API (Dockerfile, compose.yml, код, тесты)
├── contracts/        # OpenAPI/AsyncAPI — общий артефакт между командами
├── frontend/         # резерв под SPA (код ещё не начат)
├── scripts/          # вспомогательные скрипты проверки контракта
├── dev.ps1           # все команды разработчика
└── agents.md         # правила репозитория для агентов
```

Правило: сервисы не импортируют код соседей — тогда `git subtree split
--prefix=services/core` даёт самостоятельный репозиторий с историей.

## Команды (`dev.ps1`)

| Команда | Что делает |
|---|---|
| `up` / `down` | поднять / остановить стек |
| `workers` | добавить `core-queue` + `core-scheduler` (профиль `workers`) |
| `ps` / `logs [сервис]` | состояние и логи |
| `sh` | shell в контейнере `core` |
| `artisan ...` / `composer ...` | php artisan / composer внутри `core` |
| `migrate` / `fresh` / `seed` | миграции и сиды |
| `test` / `lint` / `pint` / `stan` / `qa` | Pest, Pint, Larastan, полный прогон |
| `openapi` | экспорт схемы из Laravel в `contracts/generated/openapi.json` |
| `env-check` | сверка двух env-файлов |
| `key` | генерация `APP_KEY` |
| `rebuild [сервис]` | пересборка образов |
| `clean` | `down -v` — **удаляет тома с данными БД** |

## Порты

| Сервис | Адрес | Доступ |
|---|---|---|
| API (nginx) | `http://localhost:8000` | из локальной сети |
| PostgreSQL | `127.0.0.1:5433` | только с хоста |
| Adminer | `http://127.0.0.1:8080` | только с хоста |
| Mailpit | `http://127.0.0.1:8025` | только с хоста |
| Redis | не публикуется | `docker compose -f infra/docker-compose.yml exec redis redis-cli` |

## API

| Метод | URI | Назначение |
|---|---|---|
| GET | `/up` | health-check |
| POST | `/api/v1/auth/token` | выдача Bearer-токена (201 / 422 / 429) |
| DELETE | `/api/v1/auth/token` | отзыв текущего токена (204 / 401) |
| GET | `/api/v1/me` | текущий пользователь (200 / 401) |

Авторизация — только Bearer-токены Sanctum, cookie-сессии не используются.
Полное описание — `contracts/openapi.yaml`.

## Окружение: два файла, не один

| Файл | Кто читает | Содержит |
|---|---|---|
| `infra/.env` | docker compose | порты, версии образов, `POSTGRES_*`, `XDEBUG_MODE` |
| `services/core/.env` | Laravel | `APP_*`, `DB_*`, `REDIS_*`, `FRONTEND_URL` (CORS) |

Шаблоны `*.example` коммитятся, сами `.env` — никогда. `DB_*` обязаны совпадать
с `POSTGRES_*`, иначе PostgreSQL отдаст `password authentication failed`.

## Данные и сброс

Тома `foundation-pgdata` и `foundation-redisdata` переживают `down` без `-v`.
Полный сброс — только `.\dev.ps1 clean`. Смена `POSTGRES_DB`/`POSTGRES_USER`
после первой инициализации БД не переключит: том уже содержит старый кластер.

## CI

`.github/workflows/contracts.yml` линтует OpenAPI (Spectral) и сверяет эндпоинты
кода с ручным контрактом (`openapi.yaml`). При каждом прогоне job `drift`
перегенерирует схему из Laravel и публикует актуальную версию как артефакт
прогона `openapi-generated`.

## Статус

Рабочий dev-контур поднят, API v1 с токенами работает, инструменты качества
зелёные. Дальше по плану: фронтовый контур (`frontend/compose.yml`, MSW-моки),
`CODEOWNERS` и защита `main`, вынос каталогов в отдельные репозитории.
