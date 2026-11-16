# II Core API — правила для агентов

Laravel-сервис, отдающий REST API. Каталог самодостаточен: после
`git subtree split --prefix=services/core` он становится отдельным репозиторием,
а этот файл — его корневым документом.

## 📦 Стек и версии
- **Framework**: Laravel 13.42 (API-only, `install:api`)
- **Auth**: Laravel Sanctum 4 (Bearer-токены)
- **PHP**: 8.5 (FPM, Alpine), образ `ii/core:dev`
- **БД**: PostgreSQL 18 (Alpine)
- **Кэш/Очереди**: Redis 8 (Alpine)
- **Тесты**: Pest 4.7 (поверх PHPUnit 12), `RefreshDatabase`, БД `api_test`
- **Качество кода**: Larastan 3.15 (level 6), Pint 1.32 (preset `laravel`)
- **Архитектура**: классический Laravel (Controllers / Models / Services / Actions)

## 🧱 Границы сервиса
- Сервис **не импортирует** код соседних сервисов и **не правит** `infra/` и `contracts/`.
- Контракт API — в `contracts/openapi.yaml`; изменение эндпоинта = изменение контракта.
- Версия API — в пути: `/api/v1/...`. Ломающие изменения — только новая major-версия.
- Секреты БД, Redis, SMTP не покидают сервис: фронтенду они не передаются.

## 🗂 Внутренняя структура
```
app/
├── Actions/          # один сценарий — один класс
├── Http/
│   ├── Controllers/
│   ├── Requests/     # валидация через FormRequest
│   ├── Resources/    # сериализация ответа
│   └── Middleware/
├── Models/
├── Services/
├── Providers/
└── Support/
```

- Контроллер: FormRequest → Service/Action → Resource. Бизнес-логика в контроллере не живёт.
- Модели без бизнес-логики; сложные выборки — через скоупы.
- Именование: `StoreUserRequest`, `UserResource`, `CreateUserAction`, `BillingService`.
- Тесты повторяют структуру: `tests/Feature/Api/...`, `tests/Unit/...`.

## ⚙️ Окружение
- `.env` — только Laravel, не коммитится; шаблон `.env.example`.
- `.env.testing` — тестовая БД `api_test`; шаблон `.env.testing.example`.
- `DB_*` должны совпадать с `POSTGRES_*` в `infra/.env`; проверка: `.\dev.ps1 env-check`.
- `FRONTEND_URL` — origin фронтенда для CORS (`config/cors.php`); несколько значений — через запятую.
- `.env` **не** передаётся через `env_file` в compose — Laravel читает файл сам.
- Тесты идут на PostgreSQL, а не sqlite: нужна pg-специфика (citext, jsonb, регистр).

## 🧪 Тесты и качество кода
- Pest 4.7, `RefreshDatabase` включён для `Feature`.
- Larastan level 6, пути `app/ database/ routes/`; тесты в анализ не входят (Pest-плагин есть только для Pest 5).
- Pint preset `laravel`, конфиг `pint.json`.
- Полный прогон: `.\dev.ps1 qa` или `composer qa` (lint + stan + test).
- **Контракт**: `composer openapi` (или `.\dev.ps1 openapi`) экспортирует схему в `storage/app/openapi.json` и обновляет снимок `contracts/generated/openapi.json`. Расхождение с `contracts/openapi.yaml` ловит CI.

## 🐳 Docker и образ
- `Dockerfile`: `php:8.5-fpm-alpine` + `pdo_pgsql`, `bcmath`, `exif`, `pcntl`, `zip`, `intl`, `redis`, `xdebug`.
- Расширения ставятся **отдельными `RUN`**: параллельная сборка ломает общий каталог модулей.
- `docker/entrypoint.sh` чинит права на `storage/` и `bootstrap/cache` после bind-mount Windows.
- Воркеры `core-queue` и `core-scheduler` — профиль `workers`, в базовом стеке не стартуют.
- `vendor/`, `node_modules/`, composer-кэш — именованные тома, не bind-mount.

## 🛠 Известные особенности и исправления
| Проблема | Решение |
|---|---|
| `docker-php-ext-install` падает при параллельной сборке нескольких расширений (`cp: can't stat 'modules/*'`) | Вызовы разделены по отдельным `RUN` слоям |
| `apk del` удалял runtime-библиотеки (`libpq`, `libicuio`, `libzip`) | Runtime-пакеты вынесены в отдельный `apk add` до `.build-deps` |
| PostgreSQL 18 сменил схему данных | Том монтируется в `/var/lib/postgresql`, а не `/var/lib/postgresql/data` |
| Healthcheck PG не видит переменные из `env_file` | Использован escape `$$POSTGRES_USER` в `CMD-SHELL` |
| `sockets` не собирался на Alpine 8.5 | Убран из сборки (встроен в PHP 8+, Laravel не требует) |
| Xdebug включён в образ, но отключён по умолчанию | `XDEBUG_MODE=off` в `.env`; для отладки — `debug` или `develop` |
| `MissingAppKeyException` при живом `APP_KEY` в `.env` | `.env` не передаётся через `env_file`: пустой `APP_KEY` на старте перекрыл бы файл |
| 500 `tempnam(): file created in the system's temporary directory` | `storage/` и `bootstrap/cache` приходят как `root:root`; entrypoint делает `chown`/`chmod` |
| Предупреждение `JIT is incompatible with third-party extensions` | `opcache.jit=disable` + `opcache.jit_buffer_size=0` |
| Larastan ругается на `UserFactory::definition()` | PHPDoc `array<model-property<User>, mixed>` |
| `pest-plugin-phpstan` не ставится | Существует только для Pest 5; для Pest 4 тесты исключены из PHPStan |
| Расхождение `DB_*` между `infra/.env` и `services/core/.env` | Даёт `password authentication failed`; проверка `.\dev.ps1 env-check` |

## 🗺 Маршруты и эндпоинты
| Метод | URI | Статус |
|---|---|---|
| GET | `/up` | health-check Laravel (200) |
| GET | `/` | welcome-заглушка (200) |
| POST | `/api/v1/auth/token` | выдача Bearer-токена, `throttle:6,1` (201 / 422 / 429) |
| DELETE | `/api/v1/auth/token` | отзыв текущего токена, `auth:sanctum` (204 / 401) |
| GET | `/api/v1/me` | текущий пользователь, `auth:sanctum` (200 / 401) |

Версия API — в пути. Контракт — `contracts/openapi.yaml`. Ошибки — `{"message": ...}`,
ошибки валидации — `{"message": ..., "errors": {...}}`. Cookie-сессии не используются:
только Bearer-токены, поэтому CORS без credentials.

## 📝 Правила для агентов
- Все команды — внутри контейнера: `docker compose -f infra/docker-compose.yml exec core ...` или `.\dev.ps1 ...`.
- Не использовать `make` — среда Windows.
- Не создавать вложенные `.git` в подкаталогах.
- Перед коммитом: `git ls-files | findstr /i ".env"` — только `*.example`.
- Секреты не логировать, не коммитить, не отдавать во фронтенд.
- Миграции не править после мержа в `main` — только новая миграция.
- Любое изменение публичного API сопровождается обновлением `contracts/openapi.yaml`.
