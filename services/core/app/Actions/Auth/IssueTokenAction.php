<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class IssueTokenAction
{
    /**
     * Проверяет учётные данные и выдаёт персональный токен Sanctum.
     *
     * @return array{token: string, user: User}
     *
     * @throws ValidationException
     */
    public function handle(string $email, string $password, string $deviceName): array
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
