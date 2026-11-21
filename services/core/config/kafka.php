<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Kafka
|--------------------------------------------------------------------------
|
| Брокер событий. Подключение — через расширение ext-rdkafka (в образе core).
| В CI без расширения код не исполняется: KafkaService проверяет enabled,
| а тесты с брокером пропускаются, когда broker недоступен.
|
*/

return [

    // Экстренный выключатель: false — produce() ничего не отправляет.
    'enabled' => filter_var(env('KAFKA_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // Список брокеров через запятую. Из контейнера core — kafka:9092 (docker-сеть).
    // С хоста — localhost:9094 (EXTERNAL listener).
    'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),

    'client_id' => env('KAFKA_CLIENT_ID', 'foundation-core'),

    'producer' => [
        // Сколько ждать освобождения очереди produce() перед ошибкой.
        'flush_timeout_ms' => (int) env('KAFKA_FLUSH_TIMEOUT_MS', 1000),
    ],

    'consumer' => [
        'group_id' => env('KAFKA_CONSUMER_GROUP', 'foundation-core'),
        // Пауза между опросами брокера, мс.
        'poll_interval_ms' => (int) env('KAFKA_POLL_INTERVAL_MS', 500),
        // Для новой consumer group читаем с начала топика, а не только новые сообщения.
        'offset_reset' => env('KAFKA_OFFSET_RESET', 'earliest'),
    ],

    'topics' => [
        // События пользователей: user.authenticated и т.п.
        'user_events' => env('KAFKA_TOPIC_USER_EVENTS', 'user-events'),
    ],

];
