<?php

namespace App\Http\Requests\Api\V1\Medical\MedicalLog;

use App\Models\Medicine;
use Illuminate\Foundation\Http\FormRequest;

class MedicalLogStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('medicine_name')) {
            $this->merge(['medicine_name' => trim($this->input('medicine_name', ''))]);
        }

        if (! $this->filled('logged_at')) {
            $this->merge(['logged_at' => now()]);
        }
    }

    public function rules(): array
    {
        return [
            'medicine_id' => 'nullable|integer|exists:medicines,id',
            'medicine_name' => 'required_without:medicine_id|nullable|string|max:255',
            'amount' => 'required|numeric',
            'logged_at' => 'nullable|date',
            'note' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        if (! $this->filled('medicine_id')) {
            return;
        }

        $validator->after(function ($validator) {
            $medicine = Medicine::find((int) $this->medicine_id);

            if (! $medicine) {
                return;
            }

            if (! $medicine->is_global && $medicine->user_id !== $this->user()->id) {
                $validator->errors()->add('medicine_id', __('The selected medicine does not belong to you.'));
            }
        });
    }
}
