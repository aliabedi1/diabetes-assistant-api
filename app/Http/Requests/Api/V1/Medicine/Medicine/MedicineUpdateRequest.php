<?php

namespace App\Http\Requests\Api\V1\Medicine\Medicine;

use Illuminate\Foundation\Http\FormRequest;

class MedicineUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name_en')) {
            $this->merge(['name_en' => trim($this->input('name_en', ''))]);
        }
    }

    public function rules(): array
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_fa' => 'nullable|string|max:255',
        ];
    }
}
