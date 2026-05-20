<?php

namespace App\Http\Resources\Api\V1\Authentication;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user'  => $this->user,
            'token' => $this->token,
        ];
    }
}
