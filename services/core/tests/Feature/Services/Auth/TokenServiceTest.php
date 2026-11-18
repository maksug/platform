<?php

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Services\Auth\TokenService;

/*
|--------------------------------------------------------------------------
| TokenService
|--------------------------------------------------------------------------
|
| Бизнес-логика выдачи и отзыва токенов. Feature-тесты HTTP-контракта
| проверяют роуты, здесь — чистые сервисы.
|
*/

it('issues a token for valid credentials', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $service = app(TokenService::class);
    $result = $service->issue(
        email: $user->email,
        password: 'secret-password',
        deviceName: 'unit-test',
    );

    expect($result->token)->toBeString()->not->toBeEmpty();
    expect($result->user->id)->toBe($user->id);
});

it('throws on invalid password', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    app(TokenService::class)->issue($user->email, 'wrong-password');
})->throws(InvalidCredentialsException::class);

it('throws on unknown email', function () {
    app(TokenService::class)->issue('nobody@example.com', 'password');
})->throws(InvalidCredentialsException::class);

it('revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test');

    // Имитируем аутентификацию Sanctum: currentAccessToken должен быть
    // установлен, как это делает middleware при реальном запросе.
    $user->withAccessToken($token->accessToken);

    app(TokenService::class)->revoke($user);

    expect($user->tokens()->count())->toBe(0);
});
