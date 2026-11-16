<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user);
    }
}
