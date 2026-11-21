<?php

declare(strict_types=1);

use App\Services\Kafka\KafkaService;

/*
|--------------------------------------------------------------------------
| KafkaService — side-channel
|--------------------------------------------------------------------------
|
| Kafka не должен ронять бизнес-сценарий. При выключенном kafka produce()
| возвращает false и не создаёт объекты ext-rdkafka (продюсер ленивый) —
| поэтому тест проходит даже если брокера/расширения нет.
|
*/

it('does not produce when kafka is disabled', function () {
    config(['kafka.enabled' => false]);

    expect(app(KafkaService::class)->produce('user-events', [
        'event' => 'user.authenticated',
        'user_id' => 1,
        'device_name' => 'test',
    ]))->toBeFalse();
});

it('returns bool without throwing when kafka is disabled', function () {
    config(['kafka.enabled' => false]);

    // Несколько вызовов подряд — гарантия, что Producer не кэшируется
    // в неправильном состоянии при повторном резолве.
    $service = app(KafkaService::class);

    expect($service->produce('user-events', ['event' => 'x']))
        ->toBeBool()
        ->and($service->produce('other-topic', ['event' => 'y']))
        ->toBeBool();
});
