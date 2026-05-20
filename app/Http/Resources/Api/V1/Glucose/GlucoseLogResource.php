<?php

namespace App\Http\Resources\Api\V1\Glucose;

use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GlucoseLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'glucose_amount' => $this->glucose_amount,
            'logged_at'      => $this->logged_at,
            'note'           => $this->note,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,

            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
