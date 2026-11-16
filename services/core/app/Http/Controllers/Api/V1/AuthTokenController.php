<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IssueTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthTokenController extends Controller
{
    public function store(IssueTokenRequest $request, IssueTokenAction $action): JsonResponse
    {
        $result = $action->handle(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name', 'api')->toString(),
        );

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($result['user']),
        ], 201);
    }

    public function destroy(Request $request): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            // Cookie-сессии не используются: авторизация только по Bearer-токену,
            // поэтому currentAccessToken() — это PersonalAccessToken.
            $user->currentAccessToken()->delete();
        }

        return response()->noContent();
    }
}
