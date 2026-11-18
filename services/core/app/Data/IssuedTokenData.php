<?php

namespace App\Data;

use App\Models\User;

/**
 * DTO: результат выдачи токена.
 *
 * @param  string  $token  Bearer-токен Sanctum (plainTextToken)
 * @param  User  $user  Аутентифицированный пользователь
 */
final readonly class IssuedTokenData
{
    public function __construct(
        public string $token,
        public User $user,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toResponse(): array
    {
        return [
            'token' => $this->token,
            'token_type' => 'Bearer',
            'user' => $this->user,
        ];
    }
}
