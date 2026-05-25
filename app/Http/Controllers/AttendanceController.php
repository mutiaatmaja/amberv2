<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceScanRequest;
use App\Models\AppSetting;
use App\Models\AttendanceLog;
use App\Models\QrSet;
use App\Models\QrSetPoint;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function show(Request $request, string $token, string $pointType): View|RedirectResponse
    {
        $normalizedPointType = strtoupper($pointType);
        $point = $this->resolvePoint($token, $normalizedPointType);

        if (! $point || ! $point->qrSet?->is_active) {
            return redirect()
                ->route('dashboard')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'QR tidak valid atau set QR sedang nonaktif.',
                ]);
        }

        $user = $request->user();
        $settings = AppSetting::current();
        $schedule = Schedule::query()->where('user_id', $user->id)->first();

        $todayLogs = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->whereDate('scanned_at', now()->toDateString())
            ->where('status', 'accepted')
            ->get()
            ->keyBy('point_type');

        $attendanceRows = collect(QrSet::POINT_TYPES)
            ->map(function (string $type) use ($schedule, $todayLogs, $settings): array {
                $scheduleTime = match ($type) {
                    'CHECKIN' => $schedule?->checkin_time,
                    'CHECKOUT' => $schedule?->checkout_time,
                    'PATROL_A' => $schedule?->patrol_a_time,
                    'PATROL_B' => $schedule?->patrol_b_time,
                    'PATROL_C' => $schedule?->patrol_c_time,
                    default => null,
                };

                $log = $todayLogs->get($type);
                $status = [
                    'label' => 'Belum',
                    'class' => 'bg-slate-100 text-slate-600',
                ];

                if ($log) {
                    $status = $this->resolveTimelinessStatus($log->scanned_at, $scheduleTime, $settings->late_tolerance_minutes);
                }

                return [
                    'type' => $type,
                    'label' => $this->pointLabel($type),
                    'schedule_time' => $scheduleTime,
                    'scanned_at' => $log?->scanned_at,
                    'status' => $status,
                ];
            })
            ->values();

        return view('attendance.scan', [
            'token' => $token,
            'pointType' => $normalizedPointType,
            'pointLabel' => $this->pointLabel($normalizedPointType),
            'schedule' => $schedule,
            'attendanceRows' => $attendanceRows,
            'settings' => $settings,
        ]);
    }

    public function store(StoreAttendanceScanRequest $request, string $token, string $pointType): RedirectResponse
    {
        $normalizedPointType = strtoupper($pointType);
        $point = $this->resolvePoint($token, $normalizedPointType);

        $status = 'accepted';
        $reason = null;

        if (! $point || ! $point->qrSet?->is_active) {
            $status = 'rejected';
            $reason = 'invalid_or_inactive_qr';
        }

        if ($status === 'accepted') {
            $alreadyLogged = AttendanceLog::query()
                ->where('user_id', $request->user()->id)
                ->where('point_type', $normalizedPointType)
                ->where('status', 'accepted')
                ->whereDate('scanned_at', now()->toDateString())
                ->exists();

            if ($alreadyLogged) {
                $status = 'duplicate';
                $reason = 'already_recorded_today';
            }
        }

        AttendanceLog::query()->create([
            'user_id' => $request->user()->id,
            'qr_set_id' => $point?->qr_set_id,
            'qr_set_point_id' => $point?->id,
            'point_type' => $normalizedPointType,
            'token' => $token,
            'scanned_at' => now(),
            'status' => $status,
            'reason' => $reason,
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('attendance.scan', ['token' => $token, 'pointType' => $normalizedPointType])
            ->with('toast', [
                'type' => $status === 'accepted' ? 'success' : 'error',
                'message' => match ($status) {
                    'accepted' => 'Absensi '.$this->pointLabel($normalizedPointType).' berhasil dicatat.',
                    'duplicate' => 'Absensi titik ini sudah tercatat hari ini.',
                    default => 'QR tidak valid atau set QR sedang nonaktif.',
                },
            ]);
    }

    private function resolvePoint(string $token, string $pointType): ?QrSetPoint
    {
        if (! in_array($pointType, QrSet::POINT_TYPES, true)) {
            return null;
        }

        return QrSetPoint::query()
            ->with('qrSet')
            ->where('token', $token)
            ->where('point_type', $pointType)
            ->first();
    }

    private function pointLabel(string $pointType): string
    {
        return match ($pointType) {
            'CHECKIN' => 'Checkin',
            'CHECKOUT' => 'Checkout',
            'PATROL_A' => 'Patroli A',
            'PATROL_B' => 'Patroli B',
            'PATROL_C' => 'Patroli C',
            default => $pointType,
        };
    }

    private function resolveTimelinessStatus($scannedAt, ?string $scheduleTime, int $toleranceMinutes): array
    {
        if (! $scheduleTime || ! $scannedAt) {
            return [
                'label' => 'Sudah',
                'class' => 'bg-emerald-50 text-emerald-700',
            ];
        }

        $today = now()->toDateString();
        $scheduledAt = Carbon::parse($today.' '.$scheduleTime)->addMinutes($toleranceMinutes);

        if ($scannedAt->greaterThan($scheduledAt)) {
            return [
                'label' => 'Terlambat',
                'class' => 'bg-amber-50 text-amber-700',
            ];
        }

        return [
            'label' => 'Tepat Waktu',
            'class' => 'bg-emerald-50 text-emerald-700',
        ];
    }
}
