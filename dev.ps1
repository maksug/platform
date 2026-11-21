<#
    dev.ps1 — обёртки над docker compose для локальной разработки.
    Запускается из корня репозитория, compose-файл лежит в infra/.

    Примеры:
        .\dev.ps1 up
        .\dev.ps1 artisan migrate
        .\dev.ps1 composer require laravel/sanctum
        .\dev.ps1 test
        .\dev.ps1 qa
#>
param(
    [Parameter(Position = 0)]
    [string]$Cmd = "help",
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Rest
)

$ErrorActionPreference = "Stop"

# Кириллица в выводе PowerShell 5.1
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch { }

$Root     = Split-Path -Parent $MyInvocation.MyCommand.Path
$Compose  = Join-Path $Root "infra\docker-compose.yml"
$InfraEnv = Join-Path $Root "infra\.env"
$CoreEnv  = Join-Path $Root "services\core\.env"

function Invoke-Compose {
    param([string[]]$ComposeArgs)
    docker compose -f $Compose @ComposeArgs
}

# Сравнивает DB_* в infra/.env и services/core/.env: рассинхрон даёт
# загадочные ошибки аутентификации PostgreSQL.
function Test-EnvSync {
    if (-not (Test-Path $InfraEnv) -or -not (Test-Path $CoreEnv)) {
        Write-Host "SKIP: infra/.env or services/core/.env not found" -ForegroundColor Yellow
        return
    }

    $map = @(
        @{ Infra = "POSTGRES_DB";       Core = "DB_DATABASE" },
        @{ Infra = "POSTGRES_USER";     Core = "DB_USERNAME" },
        @{ Infra = "POSTGRES_PASSWORD"; Core = "DB_PASSWORD" }
    )

    $ok = $true
    foreach ($pair in $map) {
        $i = (Select-String -Path $InfraEnv -Pattern "^$($pair.Infra)=" | Select-Object -First 1).Line
        $c = (Select-String -Path $CoreEnv  -Pattern "^$($pair.Core)="  | Select-Object -First 1).Line
        $iv = if ($i) { $i.Split('=', 2)[1].Trim() } else { "" }
        $cv = if ($c) { $c.Split('=', 2)[1].Trim() } else { "" }

        if ($iv -ne $cv) {
            Write-Host "MISMATCH $($pair.Infra)='$iv' vs $($pair.Core)='$cv'" -ForegroundColor Red
            $ok = $false
        }
    }

    if ($ok) { Write-Host "OK: DB settings match between infra/.env and services/core/.env" -ForegroundColor Green }
    else     { Write-Host "MISMATCH: align DB_* values in both env files" -ForegroundColor Red }
}

switch ($Cmd) {
    "help" {
        @'
Dev commands:
  up                Поднять базовый стек (core, nginx, pg, redis, mailpit, adminer)
  workers           Поднять воркеры: core-queue, core-scheduler, core-kafka-consumer (профиль workers)
  down              Остановить и удалить контейнеры
  ps                Состояние сервисов
  logs              Логи (по умолчанию все, можно: logs core)
  sh                Зайти в shell контейнера core
  artisan           php artisan ...
  composer          composer ...
  test              Pest (можно фильтром: test --filter=Foo)
  lint              Проверка стиля без изменений (pint --test)
  pint              Автоисправление стиля (pint)
  stan              Статический анализ (phpstan, level 6)
  openapi           Экспорт OpenAPI из Laravel в contracts/generated/openapi.json
  qa                Полный цикл: lint + stan + test
  env-check         Сверить DB_* в infra/.env и services/core/.env
  migrate           php artisan migrate
  seed              php artisan db:seed
  fresh             migrate:fresh
  key               php artisan key:generate
  rebuild core      Пересобрать конкретный сервис
  clean             Остановить и удалить тома (данные БД!)
'@
    }
    "up"      { Invoke-Compose @("up", "-d", "--build") }
    "workers" { Invoke-Compose @("--profile", "workers", "up", "-d", "core-queue", "core-scheduler", "core-kafka-consumer") }
    "down"    { Invoke-Compose @("down") }
    "ps"      { Invoke-Compose @("ps") }
    "logs"    { if ($Rest) { Invoke-Compose (@("logs", "-f") + $Rest) } else { Invoke-Compose @("logs", "-f") } }
    "sh"      { Invoke-Compose @("exec", "core", "sh") }
    "artisan" { Invoke-Compose (@("exec", "core", "php", "artisan") + $Rest) }
    "composer" { Invoke-Compose (@("exec", "core", "composer") + $Rest) }
    "test"    { Invoke-Compose (@("exec", "core", "./vendor/bin/pest") + $Rest) }
    "lint"    { Invoke-Compose @("exec", "core", "./vendor/bin/pint", "--test") }
    "pint"    { Invoke-Compose (@("exec", "core", "./vendor/bin/pint") + $Rest) }
    "stan"    { Invoke-Compose (@("exec", "core", "./vendor/bin/phpstan", "analyse", "--no-progress") + $Rest) }
    "openapi" {
        Invoke-Compose @("exec", "core", "composer", "openapi")
        $generated = Join-Path $Root "services\core\storage\app\openapi.json"
        $targetDir = Join-Path $Root "contracts\generated"
        $target    = Join-Path $targetDir "openapi.json"
        if (-not (Test-Path $targetDir)) { New-Item -ItemType Directory -Path $targetDir | Out-Null }
        Copy-Item $generated $target -Force
        Write-Host "OK: $target" -ForegroundColor Green
    }
    "qa"      { Invoke-Compose @("exec", "core", "composer", "qa") }
    "env-check" { Test-EnvSync }
    "migrate" { Invoke-Compose (@("exec", "core", "php", "artisan", "migrate") + $Rest) }
    "seed"    { Invoke-Compose (@("exec", "core", "php", "artisan", "db:seed") ) }
    "fresh"   { Invoke-Compose @("exec", "core", "php", "artisan", "migrate:fresh") }
    "key"     { Invoke-Compose @("exec", "core", "php", "artisan", "key:generate") }
    "rebuild" { if ($Rest) { Invoke-Compose (@("build") + $Rest) } else { Invoke-Compose @("build") } }
    "clean"   { Invoke-Compose @("down", "-v") }
    default   { Write-Host "Unknown: $Cmd"; Invoke-Compose @("--help") }
}
