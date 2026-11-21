# Foundation — рабочее пространство (монорепозиторий)

Репозиторий — **фундамент** для будущих проектов: конкретные продуктовые
домены ещё не выбраны, поэтому название не привязано к стеку и предметной
области. Каждый проект вырастает отсюда (копия или `git subtree split`).

Файл описывает **рабочее пространство**: раскладку каталогов, git-модель,
границы доступа команд и платформу. Правила конкретных сервисов живут рядом с кодом.

| Каталог | Документ для агентов |
|---|---|
| `services/core/` | [`services/core/agents.md`](services/core/agents.md) — Laravel API: стек, слои, тесты, QA, Docker/PHP |
| `frontend/` | [`frontend/agents.md`](frontend/agents.md) — SPA: контракт, моки, запрет на секреты бэкенда |
| `contracts/` | [`contracts/README.md`](contracts/README.md) — OpenAPI, версионирование, владельцы |
| `infra/` | этот файл, раздел «Docker-платформа» |

⚠️ Часть инструментов читает только корневой `agents.md`. Если работа идёт внутри
`services/core/` или `frontend/`, соответствующий файл нужно открыть явно — иначе
правила сервиса будут проигнорированы.

## 🗂 Структура монорепо
```
foundation/
├── infra/                    # Платформа: сеть, данные, HTTP, dev-инструменты
│   ├── docker-compose.yml    # собирает сервисы через `include:`
│   ├── .env                  # ТОЛЬКО compose (порты, POSTGRES_*)
│   ├── nginx/default.conf    # root = /var/www/core/public
│   ├── postgres/init-db.sql  # расширения + создание api_test
│   └── redis/redis.conf
├── services/
│   └── core/                 # Laravel-сервис (самодостаточная папка)
│       ├── agents.md         # правила сервиса: стек, слои, тесты, Docker/PHP
│       ├── Dockerfile        # образ сервиса
│       ├── compose.yml       # фрагмент: core + core-queue + core-scheduler
│       ├── docker/           # php.ini, fpm-pool.conf, entrypoint.sh
│       ├── .gitignore        # секреты и артефакты сервиса
│       ├── .env              # ТОЛЬКО Laravel (не коммитится)
│       └── app/ ... tests/
├── frontend/                 # резерв под SPA (пока README и agents.md)
│   ├── agents.md
│   └── README.md
├── contracts/                # OpenAPI/AsyncAPI — общий артефакт
├── .gitignore                # секреты и артефакты всего монорепо
├── dev.ps1                   # обёртки (из корня, compose в infra/)
└── agents.md
```
Правило: **сервис не импортирует код соседних сервисов**. Тогда `git subtree split --prefix=services/core` даёт готовый репозиторий с историей.

## 🌿 Git-модель: монорепо сейчас, отдельные репозитории потом

- Сейчас **один репозиторий** в корне `foundation/`, ветка `main`, первый коммит `chore: initial monorepo import`.
- Каждый каталог верхнего уровня (`infra/`, `services/core/`, `contracts/`, `frontend/`) проектируется как **самодостаточный**: не импортирует код соседей, держит свои зависимости, конфиги, `.gitignore` и `agents.md`.
- Вложенные `.git` в подкаталогах **не создавать**: они превращают каталог в gitlink и ломают `subtree split`.

### Процедура выноса каталога

```powershell
# 1. История каталога -> отдельная ветка
git subtree split --prefix=services/core -b core-standalone

# 2. Отдельный репозиторий и перенос истории
git init ..\foundation-core
git -C ..\foundation-core pull ..\foundation core-standalone

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

## 🐳 Docker-платформа (infra/)

- **`infra/docker-compose.yml`** — nginx, pg, redis, kafka, mailpit, adminer. Сервисы подключаются фрагментами через `include: ../services/core/compose.yml`.
- **Kafka** — режим KRaft (`cp-kafka:8.1.0`), один узел broker+controller, Zookeeper не нужен и не разворачивается.
- **Связь**: `nginx` → `core` (php-fpm:9000) → БД/Redis; порядок старта через healthcheck-цепочки.
- **Порты и тома платформы**: `pgdata` (`foundation-pgdata`), `redisdata` (`foundation-redisdata`), `kafkadata` (`foundation-kafkadata`); производные тома сервиса (`foundation-core-vendor`, `foundation-core-node-modules`, `foundation-core-composer-cache`) можно удалять без боли.
- **Логи**: rotation 10 МБ × 3 файла на сервис.
- **Рестарт**: приложение — `restart: "no"` (падение видно сразу), инфраструктура (pg/redis/nginx) — `unless-stopped`.
- **Конфигурация — два файла, не один**: `infra/.env` (compose) и `services/core/.env` (Laravel). Проверка совпадения `DB_*`: `.\dev.ps1 env-check`.
- **Обёртки**: `dev.ps1` в корне — `up`, `workers`, `down`, `ps`, `logs`, `sh`, `artisan`, `composer`, `test`, `lint`, `pint`, `stan`, `qa`, `env-check`, `migrate`, `fresh`, `seed`, `key`, `rebuild`, `clean`.

## 🖥 Фронтенд (зарезервировано)
- `frontend/` — пустая папка под SPA, пока только `README.md` и `agents.md`.
- Стек не выбран; бэкенд отдаёт чистый REST API, фронт на него не влияет.
- Когда появится код: свои `package.json`/`node_modules`/`.env`, Docker-фрагмент `frontend/compose.yml` + строка в `include:` у `infra/docker-compose.yml`.
- Типы API — из `contracts/openapi.yaml`, не руками.
- Порт `5173` (`VITE_PORT`) зарезервирован в `infra/.env`.
- Папка самодостаточна: `git subtree split --prefix=frontend` → отдельный репозиторий.
- **Доступ**: фронтенд-команда не получает `services/core/` и `infra/`. Пока это монорепо — изоляция через правила ревью; после выноса `frontend/` в свой репозиторий — физическая.

## 🔌 Порты (кто где слушает)
| Сервис | Хост | Доступ |
|---|---|---|
| API (nginx) | `0.0.0.0:8000` | из сети (телефон и т.п.) |
| PostgreSQL | `127.0.0.1:5433` | только с хоста ⚠️ не 5432! |
| Kafka (EXTERNAL) | `127.0.0.1:9094` | только с хоста |
| Kafka (INTERNAL) | `kafka:9092` | внутри docker-сети (core → kafka:9092) |
| Adminer | `127.0.0.1:8080` | только с хоста |
| Mailpit | `127.0.0.1:8025` | только с хоста |
| Redis | не публикуется | `docker compose -f infra/docker-compose.yml exec redis redis-cli` |

⚠️ На машине пользователя стоит **нативный PostgreSQL 17** на порту 5432 (`C:\Program Files\PostgreSQL\17`) — конфликтовать с ним нельзя, контейнерная БД специально на 5433. Внутри docker-сети Laravel ходит на `pg:5432` как обычно.

## 💾 Хранение и сброс данных
- `foundation-pgdata` (PG), `foundation-redisdata` (Redis), `foundation-kafkadata` (Kafka) — **named volumes** Docker.
- `foundation-core-vendor`, `foundation-core-node-modules`, `foundation-core-composer-cache` — производные артефакты.
- Переживают `restart`, `rm`, `up`, `build`, `down` (без `-v`).
- **Полный сброс**: только `.\dev.ps1 clean` (выполняет `down -v`) или ручное `docker volume rm`.
- ⚠️ **Ловушка**: изменение `POSTGRES_DB`/`POSTGRES_USER` в `infra/.env` после первой инициализации **не переключит** БД автоматически. Том уже содержит старый кластер. Требуется `clean` или ручное `DROP DATABASE` / `CREATE DATABASE`.

## ✅ Текущий статус
- [x] Монорепо разложено: `infra/` + `services/core/` + `contracts/` + `frontend/`, фрагмент compose на сервис.
- [x] Все сервисы healthy, связность БД/Redis из контейнера `core` подтверждена.
- [x] Laravel 13.42 установлен, `install:api` (Sanctum 4), миграции применены.
- [x] Архитектура: монолит со слоем Services (Controllers → Actions → Services → Models).
- [x] `GET /up` → 200, `GET /api/v1/me` без токена → 401.
- [x] API v1: выдача и отзыв токена Sanctum, `/api/v1/me`, CORS через `FRONTEND_URL`.
- [x] Контракт: `contracts/openapi.yaml` (0.2.0), `.spectral.yaml`, CI-линт.
- [x] Экспорт OpenAPI из Laravel (Scramble), снимок `contracts/generated/openapi.json`, CI-сверка расхождений.
- [x] Инструменты качества зелёные: Pest, Larastan level 6, Pint.
- [x] Git-репозиторий в корне: ветка `main`, коммиты `b784454`, `fb974dc`.
- [x] `.gitignore` в корне и в сервисе: `.env`, `.env.testing`, `vendor`, `node_modules` не попадают в индекс.
- [x] Правила для агентов разнесены по уровням: корень, `services/core/agents.md`, `frontend/agents.md`.

## ⏳ Следующие шаги
1. Фронтовый контур: `frontend/compose.yml`, прокси на API, MSW-моки по контракту.
2. CODEOWNERS и защита `main`; remote и первый push.
3. Вынос каталогов в отдельные репозитории через `git subtree split` с правкой относительных путей в `infra/`.
4. AsyncAPI для событий, когда появятся очереди и брокер.

## 📝 Примечания для агентов
- Не использовать `make` — среда Windows, все обёртки в `dev.ps1`.
- Все команды разработчика выполняются внутри контейнера `core` через `docker compose -f infra/docker-compose.yml exec core ...` (или `.\dev.ps1 ...`).
- `.env` не должен коммититься; шаблоны — `infra/.env.example`, `services/core/.env.example`.
- Для отладки Xdebug: `XDEBUG_MODE=develop` в `infra/.env`, затем `.\dev.ps1 up`.
- Тонкие места: смена имён БД/пользователей после первого запуска, команда `clean` безвозвратно удаляет тома, рассинхрон двух env-файлов.
- Git: ветка `main`, репозиторий один (монорепо). Вложенные `.git` в подкаталогах не создавать — это ломает `git subtree split`.
- Перед коммитом проверять индекс: `git ls-files | findstr /i ".env"` — только `*.example`.
- `.env` и `.env.testing` не коммитятся никогда; шаблоны — `.env.example`, `.env.testing.example`.
- При правке `infra/` помнить: пути `../services/core` станут проблемой при выносе сервиса в отдельный репозиторий.
- Правила сервисов — в `services/core/agents.md` и `frontend/agents.md`; не дублировать их здесь.
