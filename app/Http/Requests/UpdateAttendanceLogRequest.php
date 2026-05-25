<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'scanned_at' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(['accepted', 'rejected'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
