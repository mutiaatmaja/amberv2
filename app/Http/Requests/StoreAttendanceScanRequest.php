<?php

namespace App\Http\Requests;

use App\Models\AppSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceScanRequest extends FormRequest
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
        $requireGps = AppSetting::current()->require_gps;

        return [
            'latitude' => [$requireGps ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [$requireGps ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
