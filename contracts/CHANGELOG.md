# Changelog

Все заметные изменения контракта API. Формат — [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
версионирование — [SemVer](https://semver.org/lang/ru/).

## [0.1.0] — 2026-11-16

### Добавлено
- OpenAPI 3.1 спецификация текущего API: `GET /up`, `GET /api/user`.
- Схемы `User` и `Error`.
- Security scheme `sanctum` (Bearer-токен, без cookie-сессий).
- Правила линта `.spectral.yaml` и CI-проверка `contracts/**`.

### Планируется
- `/api/v1`: auth-эндпоинты выдачи и отзыва токена, `GET /me`.
- Единый формат ошибок (RFC 9457 `application/problem+json`).
- Экспорт схемы из Laravel и проверка расхождений в CI.
