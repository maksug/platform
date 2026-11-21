<?php

namespace App\Actions\Auth;

use App\Data\IssuedTokenData;
use App\Services\Auth\TokenService;
use App\Services\Kafka\KafkaService;
use App\Support\Actions\Action;

/**
 * Сценарий: аутентификация по email+password и выдача токена Sanctum.
 *
 * Оркестрирует сервисы, возвращает DTO для контроллера.
 * Успешный вход публикуется в Kafka как side-event (ошибку брокера
 * KafkaService логирует сам, сценарий от неё не падает).
 */
final class LoginAction extends Action
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly KafkaService $kafka,
    ) {}

    public function handle(string $email, string $password, string $device_name = 'api'): IssuedTokenData
    {
        $result = $this->tokens->issue($email, $password, $device_name);

        $this->kafka->produce((string) config('kafka.topics.user_events'), [
            'event' => 'user.authenticated',
            'user_id' => $result->user->id,
            'device_name' => $device_name,
            'occurred_at' => now()->toIso8601String(),
        ]);

        return $result;
    }
}
