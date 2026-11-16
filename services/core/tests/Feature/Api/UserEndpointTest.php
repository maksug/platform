<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| /api/user (Sanctum)
|--------------------------------------------------------------------------
|
| Проверяем контракт: без токена — 401 и JSON-ответ, с валидным
| Bearer-токеном — 200 и данные пользователя. Это первый вертикальный
| срез, который подтверждает, что API-слой (routes/api.php + Sanctum)
| работает целиком, включая БД.
|
*/

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized();
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email)
        ->assertJsonMissingPath('password');
});

it('rejects an invalid token', function () {
    $this->withToken('invalid-token-value')
        ->getJson('/api/user')
        ->assertUnauthorized();
});
