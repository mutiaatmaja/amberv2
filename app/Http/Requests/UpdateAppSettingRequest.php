<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'require_gps' => ['required', 'boolean'],
            'show_map' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'require_gps' => $this->boolean('require_gps'),
            'show_map' => $this->boolean('show_map'),
        ]);
    }
}
