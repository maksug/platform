<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Сверка контракта с кодом
|--------------------------------------------------------------------------
|
| Сравнивает эндпоинты, сгенерированные из Laravel (Scramble), с ручным
| contracts/openapi.yaml. Падает, если в коде появился эндпоинт, которого
| нет в контракте. Обратная ситуация (в контракте есть, в коде нет) —
| предупреждение: контракт может описывать запланированное изменение.
|
| Запуск: php scripts/check-openapi-drift.php
|         php scripts/check-openapi-drift.php --root=/path/to/repo
|
*/

use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__);

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--root=')) {
        $root = rtrim(substr($argument, strlen('--root=')), '/\\');
    }
}

$autoload = $root . '/services/core/vendor/autoload.php';
if (! is_file($autoload)) {
    fwrite(STDERR, "Не найден autoload: {$autoload}. Выполните composer install в services/core.\n");
    exit(2);
}

require $autoload;

$contractPath = $root . '/contracts/openapi.yaml';
$generatedPath = $root . '/contracts/generated/openapi.json';

foreach ([$contractPath, $generatedPath] as $path) {
    if (! is_file($path)) {
        fwrite(STDERR, "Не найден файл: {$path}\n");
        exit(2);
    }
}

$contract = Yaml::parseFile($contractPath);
$generatedJson = file_get_contents($generatedPath);

if ($generatedJson === false) {
    fwrite(STDERR, "Не удалось прочитать {$generatedPath}\n");
    exit(2);
}

$generated = json_decode($generatedJson, true, 512, JSON_THROW_ON_ERROR);

$methods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'];

// Scramble строит пути относительно server URL: server = http://host/api, path = /v1/me.
// В ручном контракте пути полные (/api/v1/me), поэтому приводим сгенерированные к тому же виду.
$basePath = '';
$serverUrl = $generated['servers'][0]['url'] ?? '';
$serverPath = is_string($serverUrl) ? parse_url($serverUrl, PHP_URL_PATH) : null;

if (is_string($serverPath)) {
    $basePath = rtrim($serverPath, '/');
}

$contractOps = [];
foreach (($contract['paths'] ?? []) as $path => $operations) {
    $path = (string) $path;

    // Служебные эндпоинты вне API (например /up) в сверке не участвуют.
    if ($basePath !== '' && ! str_starts_with($path, $basePath)) {
        continue;
    }

    foreach (array_keys((array) $operations) as $method) {
        $method = strtolower((string) $method);

        if (in_array($method, $methods, true)) {
            $contractOps[strtoupper($method) . ' ' . $path] = true;
        }
    }
}

$generatedOps = [];
$missing = [];

foreach (($generated['paths'] ?? []) as $path => $operations) {
    $fullPath = $basePath . (string) $path;

    foreach (array_keys((array) $operations) as $method) {
        $method = strtolower((string) $method);

        if (! in_array($method, $methods, true)) {
            continue;
        }

        $key = strtoupper($method) . ' ' . $fullPath;
        $generatedOps[$key] = true;

        if (! isset($contractOps[$key])) {
            $missing[] = $key;
        }
    }
}

$stale = array_keys(array_diff_key($contractOps, $generatedOps));

if ($missing !== []) {
    fwrite(STDERR, "Эндпоинты есть в коде, но не описаны в contracts/openapi.yaml:\n");
    foreach ($missing as $key) {
        fwrite(STDERR, "  - {$key}\n");
    }
}

if ($stale !== []) {
    fwrite(STDERR, "В контракте описаны эндпоинты, которых нет в коде:\n");
    foreach ($stale as $key) {
        fwrite(STDERR, "  - {$key}\n");
    }
}

if ($missing !== []) {
    exit(1);
}

fwrite(STDOUT, sprintf(
    "OK: контракт %d операций, код %d операций\n",
    count($contractOps),
    count($generatedOps),
));

exit(0);
