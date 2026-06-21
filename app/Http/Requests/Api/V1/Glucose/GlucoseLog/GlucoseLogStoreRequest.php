<?php

namespace App\Http\Requests\Api\V1\Glucose\GlucoseLog;

use Illuminate\Foundation\Http\FormRequest;

class GlucoseLogStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'glucose_amount' => 'required|numeric|min:1|max:1500',
            'logged_at' => 'nullable|date',
            'note' => 'nullable',
        ];
    }
}
