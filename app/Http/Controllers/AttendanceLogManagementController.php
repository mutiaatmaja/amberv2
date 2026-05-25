<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAttendanceLogRequest;
use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\QrSet;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceLogManagementController extends Controller
{
    public function index(Request $request): View
    {
        $satpams = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'satpam'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedUserId = $request->integer('user_id') ?: null;
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        $dailyRows = null;
        $pointColumns = null;
        $selectedSatpam = null;

        if ($selectedUserId) {
            AttendanceCycle::expireOpenCycles($selectedUserId);
            $selectedSatpam = User::query()->with('schedule')->find($selectedUserId);

            if ($selectedSatpam) {
                $month = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
                $schedule = $selectedSatpam->schedule;

                $pointColumns = collect(QrSet::POINT_TYPES)->map(fn (string $type): array => [
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

        return view('attendance-logs.index', [
            'satpams' => $satpams,
            'selectedUserId' => $selectedUserId,
            'selectedMonth' => $selectedMonth,
            'selectedSatpam' => $selectedSatpam,
            'pointColumns' => $pointColumns,
            'dailyRows' => $dailyRows,
        ]);
    }

    public function edit(AttendanceLog $attendanceLog): View
    {
        return view('attendance-logs.edit', [
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
            ->route('attendance-logs.index', [
                'user_id' => $attendanceLog->user_id,
                'month' => Carbon::parse($data['scanned_at'])->format('Y-m'),
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Data absensi berhasil diperbarui.',
            ]);
    }

    private function pointLabel(string $pointType): string
    {
        return match ($pointType) {
            'CHECKIN' => 'Checkin',
            'CHECKOUT' => 'Checkout',
            'PATROL_1' => 'Patroli 1',
            'STANDBY_1' => 'Standby 1',
            'PATROL_2' => 'Patroli 2',
            'STANDBY_2' => 'Standby 2',
            default => $pointType,
        };
    }

    private function scheduleTime(?Schedule $schedule, string $pointType): ?string
    {
        return match ($pointType) {
            'CHECKIN' => $schedule?->checkin_time,
            'CHECKOUT' => $schedule?->checkout_time,
            'PATROL_1' => $schedule?->patrol_1_time,
            'STANDBY_1' => $schedule?->standby_1_time,
            'PATROL_2' => $schedule?->patrol_2_time,
            'STANDBY_2' => $schedule?->standby_2_time,
            default => null,
        };
    }
}
