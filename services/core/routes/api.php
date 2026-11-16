<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Версия — в пути (/api/v1/...). Ломающие изменения выпускаются только
| новой major-версией, контракт — contracts/openapi.yaml.
|
*/

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('api.v1.auth.token.store');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])
            ->name('api.v1.auth.token.destroy');

        Route::get('/me', [MeController::class, 'show'])
            ->name('api.v1.me');
    });
});
