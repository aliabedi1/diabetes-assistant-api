<?php

namespace App\Http\Requests\Api\V1\Glucose\GlucoseLog;

use Illuminate\Foundation\Http\FormRequest;

class GlucoseChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => 'required|date',
            'to' => 'nullable|date|after_or_equal:from',
        ];
    }
}
