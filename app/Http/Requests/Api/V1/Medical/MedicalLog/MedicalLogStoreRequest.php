<?php

namespace App\Http\Requests\Api\V1\Medical\MedicalLog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MedicalLogStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount'    => 'required|numeric',
            'type'      => 'nullable|string',
            'logged_at' => 'required|date',
            'note'      => 'nullable',
        ];
    }
}
