<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| POST /api/v1/auth/token, DELETE /api/v1/auth/token
|--------------------------------------------------------------------------
|
| Выдача и отзыв персонального токена Sanctum. Ошибка учётных данных
| возвращает 422 с ошибкой валидации поля email, а не 401 — так клиент
| не путает «неверный пароль» с «нет токена».
|
*/

it('issues a token for valid credentials', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'secret-password',
        'device_name' => 'pest',
    ]);

    $response->assertCreated()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('rejects unknown email', function () {
    $this->postJson('/api/v1/auth/token', [
        'email' => 'nobody@example.com',
        'password' => 'secret-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/auth/token')
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(0);

    // В тестах приложение переиспользуется между запросами, и guard
    // кеширует пользователя. Сбрасываем его, чтобы проверить именно
    // аутентификацию по удалённому токену.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});
