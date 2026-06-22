<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceScanRequest;
use App\Models\AppSetting;
use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\CleaningSchedule;
use App\Models\QrSetPoint;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CleaningAttendanceController extends Controller
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
        $schedule = CleaningSchedule::query()->where('user_id', $user->id)->first();
        AttendanceCycle::expireOpenCycles($user->id);

        $visibleCycle = AttendanceCycle::query()
            ->with('attendanceLogs')
            ->open()
            ->where('user_id', $user->id)
            ->latest('started_at')
            ->first();

        $acceptedLogs = $visibleCycle
            ? $visibleCycle->attendanceLogs->where('status', 'accepted')->keyBy('point_type')
            : collect();

        $attendanceRows = collect(self::POINT_TYPES)
            ->map(function (string $type) use ($schedule, $acceptedLogs, $settings, $visibleCycle): array {
                $scheduleTime = $this->scheduleTimeForPointType($schedule, $type);
                $log = $acceptedLogs->get($type);
                $status = [
                    'label' => 'Belum',
                    'class' => 'bg-slate-100 text-slate-600',
                ];

                if ($log) {
                    if ($log->reason === 'expired') {
                        $status = [
                            'label' => 'Expired',
                            'class' => 'bg-rose-50 text-rose-700',
                        ];
                    } else {
                        $status = $this->resolveTimelinessStatus($log, $schedule, $settings->late_tolerance_minutes, $visibleCycle);
                    }
                } elseif ($visibleCycle && $visibleCycle->status !== AttendanceCycle::STATUS_OPEN && $type !== 'CLEANING_CHECKIN') {
                    $status = [
                        'label' => 'Tidak Absen',
                        'class' => 'bg-slate-100 text-slate-600',
                    ];
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

        $scanAvailability = $this->scanAvailabilityState(
            pointType: $normalizedPointType,
            schedule: $schedule,
            settings: $settings,
            cycle: $visibleCycle?->status === AttendanceCycle::STATUS_OPEN ? $visibleCycle : null,
            scanTime: now(),
        );

        return view('cleaning-attendance.scan', [
            'token' => $token,
            'pointType' => $normalizedPointType,
            'pointLabel' => $this->pointLabel($normalizedPointType),
            'schedule' => $schedule,
            'attendanceRows' => $attendanceRows,
            'activeCycle' => $visibleCycle,
            'settings' => $settings,
            'scanAvailability' => $scanAvailability,
        ]);
    }

    public function store(StoreAttendanceScanRequest $request, string $token, string $pointType): RedirectResponse
    {
        $normalizedPointType = strtoupper($pointType);
        $point = $this->resolvePoint($token, $normalizedPointType);

        $status = 'accepted';
        $reason = null;
        $user = $request->user();
        $settings = AppSetting::current();
        $schedule = CleaningSchedule::query()->where('user_id', $user->id)->first();
        $scanTime = now();

        AttendanceCycle::expireOpenCycles($user->id);

        $activeCycle = AttendanceCycle::query()
            ->open()
            ->where('user_id', $user->id)
            ->latest('started_at')
            ->first();

        if (! $point || ! $point->qrSet?->is_active) {
            $status = 'rejected';
            $reason = 'invalid_or_inactive_qr';
        }

        if ($status !== 'accepted') {
            $this->createAttendanceLog(
                userId: $user->id,
                point: $point,
                pointType: $normalizedPointType,
                token: $token,
                scanTime: $scanTime,
                status: $status,
                reason: $reason,
                latitude: $request->validated('latitude'),
                longitude: $request->validated('longitude'),
                windowGroup: $this->pointWindowGroup($normalizedPointType),
            );

            return $this->redirectWithMessage($token, $normalizedPointType, $status, $reason);
        }

        if (! $schedule) {
            $this->createAttendanceLog(
                userId: $user->id,
                point: $point,
                pointType: $normalizedPointType,
                token: $token,
                scanTime: $scanTime,
                status: 'rejected',
                reason: 'schedule_not_found',
                latitude: $request->validated('latitude'),
                longitude: $request->validated('longitude'),
                windowGroup: $this->pointWindowGroup($normalizedPointType),
                cycleId: $activeCycle?->id,
            );

            return $this->redirectWithMessage($token, $normalizedPointType, 'rejected', 'schedule_not_found');
        }

        if ($normalizedPointType === 'CLEANING_CHECKIN') {
            if ($activeCycle) {
                Log::info('Cleaning checkin skipped because an attendance cycle is still open', [
                    'user_id' => $user->id,
                    'attendance_cycle_id' => $activeCycle->id,
                ]);

                return $this->redirectWithMessage($token, $normalizedPointType, 'rejected', 'active_cycle_exists');
            }

            $scheduledCheckin = Carbon::parse($scanTime->toDateString().' '.$schedule->checkin_time);

            if ($scanTime->lt($scheduledCheckin->copy()->subMinutes($settings->early_tolerance_minutes))) {
                $this->createAttendanceLog(
                    userId: $user->id,
                    point: $point,
                    pointType: $normalizedPointType,
                    token: $token,
                    scanTime: $scanTime,
                    status: 'rejected',
                    reason: 'too_early',
                    latitude: $request->validated('latitude'),
                    longitude: $request->validated('longitude'),
                    windowGroup: 'CLEANING_CHECKIN',
                );

                return $this->redirectWithMessage($token, $normalizedPointType, 'rejected', 'too_early');
            }

            $cycle = AttendanceCycle::query()->create([
                'user_id' => $user->id,
                'cycle_date' => $scanTime->toDateString(),
                'started_at' => $scanTime,
                'expected_end_at' => $this->buildExpectedEndAt($scanTime, $schedule, $settings->auto_checkout_grace_minutes),
                'status' => AttendanceCycle::STATUS_OPEN,
            ]);

            $this->createAttendanceLog(
                userId: $user->id,
                point: $point,
                pointType: $normalizedPointType,
                token: $token,
                scanTime: $scanTime,
                status: 'accepted',
                reason: null,
                latitude: $request->validated('latitude'),
                longitude: $request->validated('longitude'),
                windowGroup: 'CLEANING_CHECKIN',
                cycleId: $cycle->id,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return $this->redirectWithMessage($token, $normalizedPointType, 'accepted', null);
        }

        if (! $activeCycle) {
            $this->createAttendanceLog(
                userId: $user->id,
                point: $point,
                pointType: $normalizedPointType,
                token: $token,
                scanTime: $scanTime,
                status: 'rejected',
                reason: 'no_active_cycle',
                latitude: $request->validated('latitude'),
                longitude: $request->validated('longitude'),
                windowGroup: $this->pointWindowGroup($normalizedPointType),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return $this->redirectWithMessage($token, $normalizedPointType, 'rejected', 'no_active_cycle');
        }

        $alreadyLogged = AttendanceLog::query()
            ->where('attendance_cycle_id', $activeCycle->id)
            ->where('point_type', $normalizedPointType)
            ->where('status', 'accepted')
            ->exists();

        if ($alreadyLogged) {
            Log::info('Duplicate cleaning attendance point skipped for cycle', [
                'user_id' => $user->id,
                'attendance_cycle_id' => $activeCycle->id,
                'point_type' => $normalizedPointType,
            ]);

            return $this->redirectWithMessage($token, $normalizedPointType, 'rejected', 'already_recorded_in_cycle');
        }

        $windowReason = $this->validateWindow($normalizedPointType, $activeCycle, $schedule, $settings, $scanTime);

        if ($windowReason) {
            $this->createAttendanceLog(
                userId: $user->id,
                point: $point,
                pointType: $normalizedPointType,
                token: $token,
                scanTime: $scanTime,
                status: 'rejected',
                reason: $windowReason,
                latitude: $request->validated('latitude'),
                longitude: $request->validated('longitude'),
                windowGroup: $this->pointWindowGroup($normalizedPointType),
                cycleId: $activeCycle->id,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return $this->redirectWithMessage($token, $normalizedPointType, 'rejected', $windowReason);
        }

        $this->createAttendanceLog(
            userId: $user->id,
            point: $point,
            pointType: $normalizedPointType,
            token: $token,
            scanTime: $scanTime,
            status: 'accepted',
            reason: null,
            latitude: $request->validated('latitude'),
            longitude: $request->validated('longitude'),
            windowGroup: $this->pointWindowGroup($normalizedPointType),
            cycleId: $activeCycle->id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if ($normalizedPointType === 'CLEANING_CHECKOUT') {
            $activeCycle->forceFill([
                'ended_at' => $scanTime,
                'status' => AttendanceCycle::STATUS_CLOSED,
                'checkout_mode' => AttendanceCycle::CHECKOUT_MODE_MANUAL,
            ])->save();
        }

        return $this->redirectWithMessage($token, $normalizedPointType, 'accepted', null);
    }

    private function resolvePoint(string $token, string $pointType): ?QrSetPoint
    {
        if (! in_array($pointType, self::POINT_TYPES, true)) {
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
            'CLEANING_CHECKIN' => 'Checkin',
            'CLEANING_BREAK_IN' => 'Istirahat IN',
            'CLEANING_BREAK_OUT' => 'Istirahat OUT',
            'CLEANING_CHECKOUT' => 'Checkout',
            default => $pointType,
        };
    }

    private function scheduleTimeForPointType(?CleaningSchedule $schedule, string $pointType): ?string
    {
        return match ($pointType) {
            'CLEANING_CHECKIN' => $schedule?->checkin_time,
            'CLEANING_BREAK_IN' => $schedule?->break_in_time,
            'CLEANING_BREAK_OUT' => $schedule?->break_out_time,
            'CLEANING_CHECKOUT' => $schedule?->checkout_time,
            default => null,
        };
    }

    private function pointWindowGroup(string $pointType): string
    {
        return match ($pointType) {
            'CLEANING_CHECKIN' => 'CLEANING_CHECKIN',
            'CLEANING_CHECKOUT' => 'CLEANING_CHECKOUT',
            'CLEANING_BREAK_IN', 'CLEANING_BREAK_OUT' => $pointType,
            default => 'UNKNOWN',
        };
    }

    private function resolveTimelinessStatus(
        AttendanceLog $log,
        ?CleaningSchedule $schedule,
        int $toleranceMinutes,
        ?AttendanceCycle $cycle,
    ): array {
        if (! $schedule || ! $cycle) {
            return [
                'label' => 'Sudah',
                'class' => 'bg-emerald-50 text-emerald-700',
            ];
        }

        $scheduledAt = $this->scheduledAtForPointType($cycle, $schedule, $log->point_type);

        if (! $scheduledAt) {
            return [
                'label' => 'Sudah',
                'class' => 'bg-emerald-50 text-emerald-700',
            ];
        }

        $scheduledAt = $scheduledAt->addMinutes($toleranceMinutes);

        if ($log->scanned_at->greaterThan($scheduledAt)) {
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

    private function buildExpectedEndAt(Carbon $scanTime, CleaningSchedule $schedule, int $graceMinutes): Carbon
    {
        $expectedCheckout = Carbon::parse($scanTime->toDateString().' '.$schedule->checkout_time);

        if ($schedule->checkout_time < $schedule->checkin_time) {
            $expectedCheckout->addDay();
        }

        return $expectedCheckout->addMinutes($graceMinutes);
    }

    private function scheduledAtForPointType(AttendanceCycle $cycle, CleaningSchedule $schedule, string $pointType): ?Carbon
    {
        $scheduleTime = $this->scheduleTimeForPointType($schedule, $pointType);

        if (! $scheduleTime) {
            return null;
        }

        $scheduledAt = Carbon::parse($cycle->cycle_date->toDateString().' '.$scheduleTime);

        if ($schedule->checkin_time && $scheduleTime < $schedule->checkin_time) {
            $scheduledAt->addDay();
        }

        return $scheduledAt;
    }

    /**
     * @return array{disabled: bool, message: ?string}
     */
    private function scanAvailabilityState(
        string $pointType,
        ?CleaningSchedule $schedule,
        AppSetting $settings,
        ?AttendanceCycle $cycle,
        Carbon $scanTime,
    ): array {
        if (! $schedule) {
            return [
                'disabled' => false,
                'message' => null,
            ];
        }

        if ($pointType === 'CLEANING_CHECKIN') {
            $scheduledAt = Carbon::parse($scanTime->toDateString().' '.$schedule->checkin_time);
        } elseif ($cycle) {
            $scheduledAt = $this->scheduledAtForPointType($cycle, $schedule, $pointType);
        } else {
            $scheduleTime = $this->scheduleTimeForPointType($schedule, $pointType);
            $scheduledAt = Carbon::parse($scanTime->toDateString().' '.$scheduleTime);

            if ($schedule->checkin_time && $scheduleTime < $schedule->checkin_time) {
                $scheduledAt->addDay();
            }
        }

        if (! isset($scheduledAt) || ! $scheduledAt) {
            return [
                'disabled' => false,
                'message' => null,
            ];
        }

        if ($scanTime->lt($scheduledAt->copy()->subMinutes($settings->early_tolerance_minutes))) {
            return [
                'disabled' => true,
                'message' => 'Jadwal Anda Belum Dimulai',
            ];
        }

        return [
            'disabled' => false,
            'message' => null,
        ];
    }

    private function validateWindow(
        string $pointType,
        AttendanceCycle $cycle,
        CleaningSchedule $schedule,
        AppSetting $settings,
        Carbon $scanTime,
    ): ?string {
        $windowGroup = $this->pointWindowGroup($pointType);

        if ($windowGroup === 'CLEANING_CHECKOUT') {
            $checkoutAt = $this->scheduledAtForPointType($cycle, $schedule, 'CLEANING_CHECKOUT');

            if ($checkoutAt && $scanTime->lt($checkoutAt->copy()->subMinutes($settings->early_tolerance_minutes))) {
                return 'too_early';
            }

            return null;
        }

        $range = $this->windowRange($windowGroup, $cycle, $schedule);

        if (! $range) {
            return null;
        }

        if ($scanTime->lt($range['start']->copy()->subMinutes($settings->early_tolerance_minutes))) {
            return 'too_early';
        }

        if ($scanTime->greaterThan($range['end'])) {
            return 'outside_window';
        }

        return null;
    }

    /**
     * @return array{start: Carbon, end: Carbon}|null
     */
    private function windowRange(string $windowGroup, AttendanceCycle $cycle, CleaningSchedule $schedule): ?array
    {
        $sequence = ['CLEANING_BREAK_IN', 'CLEANING_BREAK_OUT'];
        $position = array_search($windowGroup, $sequence, true);

        if ($position === false) {
            return null;
        }

        $start = $this->scheduledAtForPointType($cycle, $schedule, $windowGroup);

        if (! $start) {
            return null;
        }

        $nextPointType = $sequence[$position + 1] ?? 'CLEANING_CHECKOUT';
        $nextStart = $this->scheduledAtForPointType($cycle, $schedule, $nextPointType);

        $end = $nextStart ? $nextStart->copy()->subSecond() : $cycle->expected_end_at;

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function createAttendanceLog(
        int $userId,
        ?QrSetPoint $point,
        string $pointType,
        string $token,
        Carbon $scanTime,
        string $status,
        ?string $reason,
        ?float $latitude,
        ?float $longitude,
        string $windowGroup,
        ?int $cycleId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AttendanceLog {
        return AttendanceLog::query()->create([
            'attendance_cycle_id' => $cycleId,
            'user_id' => $userId,
            'qr_set_id' => $point?->qr_set_id,
            'qr_set_point_id' => $point?->id,
            'window_group' => $windowGroup,
            'point_type' => $pointType,
            'token' => $token,
            'scanned_at' => $scanTime,
            'status' => $status,
            'reason' => $reason,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    private function redirectWithMessage(string $token, string $pointType, string $status, ?string $reason): RedirectResponse
    {
        return redirect()
            ->route('cleaning-attendance.scan', ['token' => $token, 'pointType' => $pointType])
            ->with('toast', [
                'type' => $status === 'accepted' ? 'success' : 'error',
                'message' => $this->toastMessage($status, $pointType, $reason),
            ]);
    }

    private function toastMessage(string $status, string $pointType, ?string $reason): string
    {
        if ($status === 'accepted') {
            return 'Absensi '.$this->pointLabel($pointType).' berhasil dicatat.';
        }

        return match ($reason) {
            'too_early' => 'Absensi terlalu cepat dari jadwal yang diizinkan.',
            'outside_window' => 'Jadwal untuk titik ini sudah lewat atau sudah masuk sesi berikutnya.',
            'no_active_cycle' => 'Lakukan checkin terlebih dahulu untuk membuka siklus absensi.',
            'active_cycle_exists' => 'Anda Sudah Absen jadi Masih ada siklus absensi yang aktif. Selesaikan checkout terlebih dahulu jika waktunya sudah tiba.',
            'schedule_not_found' => 'Jadwal kebersihan belum tersedia.',
            'already_recorded_in_cycle' => 'Absensi titik ini sudah tercatat pada siklus aktif.',
            default => 'QR tidak valid atau set QR sedang nonaktif.',
        };
    }
}
