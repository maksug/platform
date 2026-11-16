-- ============================================================
-- Расширения PostgreSQL для удобства разработки
-- Запускаются один раз при первичной инициализации тома (pgdata)
-- ============================================================

CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS citext;

-- Примечание: citext полезна, если нужны case-insensitive текстовые поля
-- (например, email-адреса). pgcrypto — генерация криптографических ID.
-- "uuid-ossp" — функция uuid_generate_v4().

-- ============================================================
-- Тестовая база
-- Тесты исполняются на том же движке, что и продакшн (PostgreSQL),
-- а не на sqlite :memory: — иначе не увидим pg-специфику: citext,
-- jsonb, регистронезависимость, order-by с NULL, ограничения.
-- ============================================================

SELECT 'CREATE DATABASE api_test OWNER api'
WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'api_test')\gexec
