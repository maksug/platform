# Portfolio — фронтенд-приложение

Vue 3 + TypeScript + Vite, запускается в Docker-контейнере.

## Запуск

```powershell
# Поднять dev-сервер
.\dev.ps1 frontend

# Логи
.\dev.ps1 logs portfolio

# Остановить
.\dev.ps1 down
```

## Команды

```powershell
# type-check + build
.\dev.ps1 npm portfolio run build

# type-check только
.\dev.ps1 npm portfolio run type-check
```

## Структура

| Файл | Назначение |
|---|---|
| `src/App.vue` | корневой компонент (лендинг портфолио) |
| `src/main.ts` | точка входа |
| `src/styles/base.css` | reset + CSS-переменные |
| `vite.config.ts` | Vite (host 0.0.0.0, polling для bind-mount Windows) |
| `Dockerfile` | образ `node:22-alpine`, tini, npm ci |
| `compose.yml` | сервис `portfolio`, профиль `frontend` |

## Правила

- TypeScript strict, type-check через `vue-tsc --build` (project references).
- `.env` не коммитится; шаблон — `.env.example`.
- `node_modules` и `dist` живут в именованных томах, не в bind-mount.
- Порт: `${PORTFOLIO_PORT:-5173}` (хост) → `5173` (контейнер).
- Исходники на хосте доступны через bind-mount; изменения сразу видны в dev-сервере.
