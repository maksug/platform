<?php

namespace App\Actions\Auth;

use App\Data\IssuedTokenData;
use App\Services\Auth\TokenService;
use App\Support\Actions\Action;

/**
 * Сценарий: аутентификация по email+password и выдача токена Sanctum.
 *
 * Оркестрирует сервисы, возвращает DTO для контроллера.
 */
final class LoginAction extends Action
{
    public function __construct(
        private readonly TokenService $tokens,
    ) {}

    public function handle(string $email, string $password, string $device_name = 'api'): IssuedTokenData
    {
        return $this->tokens->issue($email, $password, $device_name);
    }
}
