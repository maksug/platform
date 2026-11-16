<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| GET /api/v1/me (Sanctum)
|--------------------------------------------------------------------------
|
| Контракт: без токена — 401 и JSON-ответ, с валидным Bearer-токеном —
| 200 и данные пользователя в обёртке data.
|
*/

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized();
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonMissingPath('data.password');
});

it('rejects an invalid token', function () {
    $this->withToken('invalid-token-value')
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});
