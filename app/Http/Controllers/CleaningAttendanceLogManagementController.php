<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAttendanceLogRequest;
use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\CleaningSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CleaningAttendanceLogManagementController extends Controller
{
    /**
     * @var list<string>
     */
    private const POINT_TYPES = [
        'CLEANING_CHECKIN',
        'CLEANING_BREAK_IN',
        'CLEANING_BREAK_OUT',
        'CLEANING_CHECKOUT',
    ];

    public function index(Request $request): View
    {
        $cleaningUsers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'kebersihan'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedUserId = $request->integer('user_id') ?: null;
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        $dailyRows = null;
        $pointColumns = null;
        $selectedCleaningUser = null;

        if ($selectedUserId) {
            AttendanceCycle::expireOpenCycles($selectedUserId);
            $selectedCleaningUser = User::query()->with('cleaningSchedule')->find($selectedUserId);

            if ($selectedCleaningUser) {
                $month = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
                $schedule = $selectedCleaningUser->cleaningSchedule;

                $pointColumns = collect(self::POINT_TYPES)->map(fn (string $type): array => [
                    'point_type' => $type,
                    'label' => $this->pointLabel($type),
                    'schedule_time' => $this->scheduleTime($schedule, $type),
                ]);

                $cycles = AttendanceCycle::query()
                    ->with([
                        'attendanceLogs' => fn ($query) => $query
                            ->where('status', 'accepted')
                            ->orderBy('scanned_at'),
                    ])
                    ->where('user_id', $selectedUserId)
                    ->whereBetween('cycle_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
                    ->orderBy('cycle_date')
                    ->get();

                $cycleLookup = $cycles->keyBy(
                    fn (AttendanceCycle $cycle): string => $cycle->cycle_date->format('Y-m-d')
                );

                $dailyRows = collect(range(1, $month->daysInMonth))
                    ->map(function (int $day) use ($month, $cycleLookup, $pointColumns): array {
                        $date = $month->copy()->setDay($day);
                        /** @var AttendanceCycle|null $cycle */
                        $cycle = $cycleLookup->get($date->format('Y-m-d'));
                        $cycleLogs = $cycle?->attendanceLogs?->keyBy('point_type') ?? collect();

                        $points = $pointColumns->map(function (array $col) use ($cycle, $cycleLogs): array {
                            /** @var AttendanceLog|null $log */
                            $log = $cycleLogs->get($col['point_type']);

                            return [
                                'log' => $log,
                                'display_time' => $log?->scanned_at->format('H:i') ?? '-',
                                'is_missed' => $cycle && ! $log,
                                'is_expired' => $log?->reason === 'expired',
                            ];
                        });

                        return [
                            'date' => $date,
                            'cycle' => $cycle,
                            'points' => $points,
                        ];
                    });
            }
        }

        return view('cleaning-attendance-logs.index', [
            'cleaningUsers' => $cleaningUsers,
            'selectedUserId' => $selectedUserId,
            'selectedMonth' => $selectedMonth,
            'selectedCleaningUser' => $selectedCleaningUser,
            'pointColumns' => $pointColumns,
            'dailyRows' => $dailyRows,
        ]);
    }

    public function edit(AttendanceLog $attendanceLog): View
    {
        return view('cleaning-attendance-logs.edit', [
            'log' => $attendanceLog->load('user'),
        ]);
    }

    public function update(UpdateAttendanceLogRequest $request, AttendanceLog $attendanceLog): RedirectResponse
    {
        $data = $request->validated();

        $attendanceLog->scanned_at = $data['scanned_at'];
        $attendanceLog->status = $data['status'];
        $attendanceLog->reason = $data['reason'] ?? null;
        $attendanceLog->save();

        return redirect()
            ->route('cleaning-attendance-logs.index', [
                'user_id' => $attendanceLog->user_id,
                'month' => Carbon::parse($data['scanned_at'])->format('Y-m'),
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Data absensi kebersihan berhasil diperbarui.',
            ]);
    }

    private function pointLabel(string $pointType): string
    {
        return match ($pointType) {
            'CLEANING_CHECKIN' => 'Checkin',
            'CLEANING_BREAK_IN' => 'Istirahat IN',
            'CLEANING_BREAK_OUT' => 'Istirahat OUT',
            'CLEANING_CHECKOUT' => 'Checkout',
            default => $pointType,
        };
    }

    private function scheduleTime(?CleaningSchedule $schedule, string $pointType): ?string
    {
        return match ($pointType) {
            'CLEANING_CHECKIN' => $schedule?->checkin_time,
            'CLEANING_BREAK_IN' => $schedule?->break_in_time,
            'CLEANING_BREAK_OUT' => $schedule?->break_out_time,
            'CLEANING_CHECKOUT' => $schedule?->checkout_time,
            default => null,
        };
    }
}
