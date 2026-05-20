<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Resources\Api\V1\Glucose\GlucoseLogResource;
use App\Http\Resources\Api\V1\Medical\MedicalLogResource;
use App\Http\Resources\Api\V1\User\Role\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'username'          => $this->username,
            'name'              => $this->name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,

            'glucose_logs' => GlucoseLogResource::collection($this->whenLoaded('glucose_logs')),
            'medical_logs' => MedicalLogResource::collection($this->whenLoaded('medical_logs')),
            'roles'        => RoleResource::collection($this->whenLoaded('roles')),
        ];
//        'default_address' => new AgentAddressResource($this->whenLoaded('address_default')),
//        'projects' => ProjectResource::collection($this->whenLoaded('projects')),
    }
}
