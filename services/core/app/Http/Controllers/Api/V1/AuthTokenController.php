<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IssueTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthTokenController extends Controller
{
    /**
     * POST /api/v1/auth/token
     */
    public function store(IssueTokenRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->handle(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            device_name: $request->string('device_name', 'api')->toString(),
        );

        return response()->json([
            'token' => $result->token,
            'token_type' => 'Bearer',
            'user' => new UserResource($result->user),
        ], 201);
    }

    /**
     * DELETE /api/v1/auth/token
     */
    public function destroy(Request $request, LogoutAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle(user: $user);

        return response()->noContent();
    }
}
