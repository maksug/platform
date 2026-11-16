<?php

namespace App\Http\Resources;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $this->toIso8601($user->email_verified_at),
            'created_at' => $this->toIso8601($user->created_at),
            'updated_at' => $this->toIso8601($user->updated_at),
        ];
    }

    private function toIso8601(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface
            ? $value->format(DATE_ATOM)
            : null;
    }
}
