<?php

namespace App\Http\Resources\Api\V1\Glucose;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GlucoseLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'glucose_amount' => $this->glucose_amount,
            'logged_at' => $this->logged_at,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
