<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $schedule = $this->route('schedule');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('schedules', 'user_id')->ignore($schedule?->id),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $user = User::query()->with('roles')->find($value);

                    if (! $user || ! $user->hasRole('satpam')) {
                        $fail('Jadwal hanya dapat diberikan untuk pengguna dengan role Satpam.');
                    }
                },
            ],
            'checkin_time' => ['required', 'date_format:H:i'],
            'checkout_time' => ['required', 'date_format:H:i', 'after:checkin_time'],
            'patrol_a_time' => ['required', 'date_format:H:i', 'after_or_equal:checkin_time'],
            'patrol_b_time' => ['required', 'date_format:H:i', 'after:patrol_a_time'],
            'patrol_c_time' => ['required', 'date_format:H:i', 'after:patrol_b_time'],
        ];
    }
}
