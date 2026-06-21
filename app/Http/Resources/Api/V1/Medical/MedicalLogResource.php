<?php

namespace App\Http\Resources\Api\V1\Medical;

use App\Http\Resources\Api\V1\Medicine\MedicineResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'type' => $this->type,
            'logged_at' => $this->logged_at,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'medicine' => $this->whenLoaded('medicine', fn ($m) => $m ? new MedicineResource($m) : null),
        ];
    }
}
