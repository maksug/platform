<?php

namespace App\Services\Auth;

use App\Data\IssuedTokenData;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class TokenService
{
    /**
     * Выдаёт токен Sanctum после проверки учётных данных.
     *
     * @throws InvalidCredentialsException
     */
    public function issue(string $email, string $password, string $deviceName = 'api'): IssuedTokenData
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, (string) $user->password)) {
            throw new InvalidCredentialsException;
        }

        /** @var string $token */
        $token = $user->createToken($deviceName)->plainTextToken;

        return new IssuedTokenData(token: $token, user: $user);
    }

    /**
     * Отзывает текущий токен пользователя.
     */
    public function revoke(User $user): void
    {
        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        $token->delete();
    }
}
