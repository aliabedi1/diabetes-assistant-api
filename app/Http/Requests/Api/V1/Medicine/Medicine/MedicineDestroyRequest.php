<?php

namespace App\Http\Requests\Api\V1\Medicine\Medicine;

use Illuminate\Foundation\Http\FormRequest;

class MedicineDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
