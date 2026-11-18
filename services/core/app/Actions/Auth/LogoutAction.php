<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\TokenService;
use App\Support\Actions\Action;

/**
 * Сценарий: отзывает текущий токен Sanctum.
 */
final class LogoutAction extends Action
{
    public function __construct(
        private readonly TokenService $tokens,
    ) {}

    public function handle(User $user): void
    {
        $this->tokens->revoke($user);
    }
}
