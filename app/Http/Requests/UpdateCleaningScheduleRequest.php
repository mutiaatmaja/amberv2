<?php

namespace App\Http\Requests;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCleaningScheduleRequest extends FormRequest
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
        $cleaningSchedule = $this->route('cleaning_schedule');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('cleaning_schedules', 'user_id')->ignore($cleaningSchedule?->id),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $user = User::query()->with('roles')->find($value);

                    if (! $user || ! $user->hasRole('kebersihan')) {
                        $fail('Jadwal hanya dapat diberikan untuk pengguna dengan role Kebersihan.');
                    }
                },
            ],
            'checkin_time' => ['required', 'date_format:H:i'],
            'break_in_time' => ['required', 'date_format:H:i'],
            'break_out_time' => ['required', 'date_format:H:i'],
            'checkout_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateScheduleTimeline($validator);
            },
        ];
    }

    private function validateScheduleTimeline(Validator $validator): void
    {
        $sequence = [
            ['field' => 'checkin_time', 'allow_equal' => true],
            ['field' => 'break_in_time', 'allow_equal' => false],
            ['field' => 'break_out_time', 'allow_equal' => false],
            ['field' => 'checkout_time', 'allow_equal' => false],
        ];

        $previous = null;

        foreach ($sequence as $index => $item) {
            $value = $this->string($item['field'])->toString();

            if ($value === '') {
                return;
            }

            $current = Carbon::createFromFormat('Y-m-d H:i', '2000-01-01 '.$value);

            if ($index === 0) {
                $previous = $current;

                continue;
            }

            while ($item['allow_equal'] ? $current->lt($previous) : $current->lte($previous)) {
                $current->addDay();
            }

            $previous = $current;
        }
    }
}
