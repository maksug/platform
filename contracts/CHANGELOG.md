# Changelog

Все заметные изменения контракта API. Формат — [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
версионирование — [SemVer](https://semver.org/lang/ru/).

## [Unreleased]

### Инструментарий
- Экспорт OpenAPI из Laravel (Scramble) в `contracts/generated/openapi.json`.
- CI-проверка расхождений: снимок против кода и покрытие эндпоинтов контрактом.
- Команда `.\dev.ps1 openapi` для перегенерации снимка.

## [0.2.0] — 2026-11-16

### Добавлено
- `POST /api/v1/auth/token` — выдача Bearer-токена Sanctum, `201`; лимит 6 попыток в минуту.
- `DELETE /api/v1/auth/token` — отзыв текущего токена, `204`.
- `GET /api/v1/me` — текущий пользователь в обёртке `data`.
- Схемы `TokenRequest`, `TokenResponse`, `ValidationError`, `UserEnvelope`.
- CORS для origin фронтенда через `FRONTEND_URL`.

### Изменено
- Все прикладные маршруты перенесены под `/api/v1`.
- Неверные учётные данные — `422` с ошибкой поля `email`, а не `401`.

### Удалено
- `GET /api/user` — заменён на `GET /api/v1/me`.

## [0.1.0] — 2026-11-16

### Добавлено
- OpenAPI 3.1 спецификация текущего API: `GET /up`, `GET /api/user`.
- Схемы `User` и `Error`.
- Security scheme `sanctum` (Bearer-токен, без cookie-сессий).
- Правила линта `.spectral.yaml` и CI-проверка `contracts/**`.
