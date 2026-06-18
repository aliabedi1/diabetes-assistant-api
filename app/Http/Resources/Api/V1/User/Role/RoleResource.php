<?php

namespace App\Http\Resources\Api\V1\User\Role;

use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'name'       => $this->name,
            'slug'       => $this->slug,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'users' => UserResource::collection($this->whenLoaded('users')),

        ];
    }
}
