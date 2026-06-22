<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AttendanceLog;
use App\Models\CleaningSchedule;
use App\Models\QrSet;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * @var list<string>
     */
    private const CLEANING_POINT_TYPES = [
        'CLEANING_CHECKIN',
        'CLEANING_BREAK_IN',
        'CLEANING_BREAK_OUT',
        'CLEANING_CHECKOUT',
    ];

    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing(['roles', 'schedule', 'cleaningSchedule']);

        if ($user->hasRole('satpam')) {
            return view('dashboard', $this->buildSatpamDashboard($user));
        }

        if ($user->hasRole('kebersihan')) {
            return view('dashboard', $this->buildCleaningDashboard($user));
        }

        return view('dashboard', $this->buildDefaultDashboard($user));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDefaultDashboard(User $user): array
    {
        return [
            'user' => $user,
            'isSatpam' => false,
            'isCleaning' => false,
            'summaryCards' => [
                [
                    'label' => 'Status Login',
                    'value' => 'Aktif',
                    'description' => 'Sesi Anda berjalan normal.',
                ],
                [
                    'label' => 'Role Aktif',
                    'value' => $user->roles->pluck('display_name')->implode(', ') ?: 'Belum diatur',
                    'description' => 'Role diambil dari pengaturan akses pengguna.',
                ],
                [
                    'label' => 'Waktu Akses',
                    'value' => now()->format('H:i'),
                    'description' => 'Jam akses terakhir ke dashboard.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSatpamDashboard(User $user): array
    {
        $schedule = $user->schedule;
        $settings = AppSetting::current();

        $todayAcceptedLogs = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->whereDate('scanned_at', today())
            ->where('status', 'accepted')
            ->get()
            ->keyBy('point_type');

        $recentAcceptedLogs = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->where('scanned_at', '>=', now()->subDays(29)->startOfDay())
            ->orderBy('scanned_at')
            ->get();

        $recentMonthlyLogs = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->orderBy('scanned_at')
            ->get();

        $todayProcessRows = collect(QrSet::POINT_TYPES)
            ->map(function (string $pointType) use ($schedule, $todayAcceptedLogs, $settings): array {
                $scheduleTime = $this->scheduleTimeForPointType($schedule, $pointType);
                $log = $todayAcceptedLogs->get($pointType);

                $status = [
                    'label' => 'Belum Absen',
                    'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                ];

                if ($log) {
                    $status = $this->resolveTimelinessStatus(
                        $log->scanned_at,
                        $scheduleTime,
                        $settings->late_tolerance_minutes
                    );
                }

                return [
                    'label' => $this->pointLabel($pointType),
                    'schedule_time' => $scheduleTime,
                    'scanned_at' => $log?->scanned_at,
                    'status' => $status,
                ];
            })
            ->values();

        return [
            'user' => $user,
            'isSatpam' => true,
            'isCleaning' => false,
            'schedule' => $schedule,
            'summaryCards' => [
                [
                    'label' => 'Total Absen 1 Bulan',
                    'value' => $recentAcceptedLogs->count().' kali',
                    'description' => 'Seluruh absensi accepted dalam 30 hari terakhir.',
                ],
                [
                    'label' => 'Tepat Waktu',
                    'value' => $this->countOnTime($recentAcceptedLogs, $schedule, $settings->late_tolerance_minutes).' kali',
                    'description' => 'Absensi yang masih berada di batas toleransi.',
                ],
                [
                    'label' => 'Terlambat',
                    'value' => $this->countLate($recentAcceptedLogs, $schedule, $settings->late_tolerance_minutes).' kali',
                    'description' => 'Absensi yang melewati jadwal + toleransi.',
                ],
                [
                    'label' => 'Hari Aktif',
                    'value' => $recentAcceptedLogs
                        ->groupBy(fn (AttendanceLog $log) => $log->scanned_at->toDateString())
                        ->count().' hari',
                    'description' => 'Jumlah hari berbeda dengan catatan absensi.',
                ],
            ],
            'todayProcessRows' => $todayProcessRows,
            'dailyRecapRows' => $this->buildDailyRecapRows($recentAcceptedLogs, $schedule, $settings->late_tolerance_minutes),
            'monthlyRecapRows' => $this->buildMonthlyRecapRows($recentMonthlyLogs, $schedule, $settings->late_tolerance_minutes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCleaningDashboard(User $user): array
    {
        $schedule = $user->cleaningSchedule;
        $settings = AppSetting::current();

        $todayAcceptedLogs = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->whereDate('scanned_at', today())
            ->where('status', 'accepted')
            ->whereIn('point_type', self::CLEANING_POINT_TYPES)
            ->get()
            ->keyBy('point_type');

        $recentAcceptedLogs = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->whereIn('point_type', self::CLEANING_POINT_TYPES)
            ->where('scanned_at', '>=', now()->subDays(29)->startOfDay())
            ->orderBy('scanned_at')
            ->get();

        $recentMonthlyLogs = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->whereIn('point_type', self::CLEANING_POINT_TYPES)
            ->orderBy('scanned_at')
            ->get();

        $todayProcessRows = collect(self::CLEANING_POINT_TYPES)
            ->map(function (string $pointType) use ($schedule, $todayAcceptedLogs, $settings): array {
                $scheduleTime = $this->scheduleTimeForCleaningPointType($schedule, $pointType);
                $log = $todayAcceptedLogs->get($pointType);

                $status = [
                    'label' => 'Belum Absen',
                    'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                ];

                if ($log) {
                    $status = $this->resolveTimelinessStatus(
                        $log->scanned_at,
                        $scheduleTime,
                        $settings->late_tolerance_minutes
                    );
                }

                return [
                    'label' => $this->cleaningPointLabel($pointType),
                    'schedule_time' => $scheduleTime,
                    'scanned_at' => $log?->scanned_at,
                    'status' => $status,
                ];
            })
            ->values();

        return [
            'user' => $user,
            'isSatpam' => false,
            'isCleaning' => true,
            'schedule' => $schedule,
            'summaryCards' => [
                [
                    'label' => 'Total Absen 1 Bulan',
                    'value' => $recentAcceptedLogs->count().' kali',
                    'description' => 'Seluruh absensi accepted dalam 30 hari terakhir.',
                ],
                [
                    'label' => 'Tepat Waktu',
                    'value' => $this->countOnTimeCleaning($recentAcceptedLogs, $schedule, $settings->late_tolerance_minutes).' kali',
                    'description' => 'Absensi yang masih berada di batas toleransi.',
                ],
                [
                    'label' => 'Terlambat',
                    'value' => $this->countLateCleaning($recentAcceptedLogs, $schedule, $settings->late_tolerance_minutes).' kali',
                    'description' => 'Absensi yang melewati jadwal + toleransi.',
                ],
                [
                    'label' => 'Hari Aktif',
                    'value' => $recentAcceptedLogs
                        ->groupBy(fn (AttendanceLog $log) => $log->scanned_at->toDateString())
                        ->count().' hari',
                    'description' => 'Jumlah hari berbeda dengan catatan absensi.',
                ],
            ],
            'todayProcessRows' => $todayProcessRows,
            'dailyRecapRows' => $this->buildDailyRecapRowsCleaning($recentAcceptedLogs, $schedule, $settings->late_tolerance_minutes),
            'monthlyRecapRows' => $this->buildMonthlyRecapRowsCleaning($recentMonthlyLogs, $schedule, $settings->late_tolerance_minutes),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyRecapRows(Collection $logs, ?Schedule $schedule, int $toleranceMinutes): array
    {
        $logsByDate = $logs->groupBy(fn (AttendanceLog $log) => $log->scanned_at->toDateString());

        return collect(range(29, 0))
            ->map(function (int $offset) use ($logsByDate, $schedule, $toleranceMinutes): array {
                $date = now()->subDays($offset)->startOfDay();
                $logsForDate = $logsByDate->get($date->toDateString(), collect());

                return [
                    'date' => $date,
                    'total' => $logsForDate->count(),
                    'on_time' => $this->countOnTime($logsForDate, $schedule, $toleranceMinutes),
                    'late' => $this->countLate($logsForDate, $schedule, $toleranceMinutes),
                    'last_scan_at' => $logsForDate->max('scanned_at'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlyRecapRows(Collection $logs, ?Schedule $schedule, int $toleranceMinutes): array
    {
        if ($logs->isEmpty()) {
            return [
                [
                    'month' => now()->startOfMonth(),
                    'total' => 0,
                    'on_time' => 0,
                    'late' => 0,
                    'active_days' => 0,
                ],
            ];
        }

        $logsByMonth = $logs->groupBy(fn (AttendanceLog $log) => $log->scanned_at->format('Y-m'));
        $startMonth = Carbon::parse($logs->first()->scanned_at)->startOfMonth();
        $endMonth = now()->startOfMonth();

        return collect(range(0, $startMonth->diffInMonths($endMonth)))
            ->map(function (int $offset) use ($logsByMonth, $schedule, $toleranceMinutes, $startMonth): array {
                $month = $startMonth->copy()->addMonthsNoOverflow($offset);
                $logsForMonth = $logsByMonth->get($month->format('Y-m'), collect());

                return [
                    'month' => $month,
                    'total' => $logsForMonth->count(),
                    'on_time' => $this->countOnTime($logsForMonth, $schedule, $toleranceMinutes),
                    'late' => $this->countLate($logsForMonth, $schedule, $toleranceMinutes),
                    'active_days' => $logsForMonth->groupBy(fn (AttendanceLog $log) => $log->scanned_at->toDateString())->count(),
                ];
            })
            ->all();
    }

    private function countOnTime(Collection $logs, ?Schedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(fn (AttendanceLog $log): bool => ! $this->isLate($log, $schedule, $toleranceMinutes))->count();
    }

    private function countLate(Collection $logs, ?Schedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(fn (AttendanceLog $log): bool => $this->isLate($log, $schedule, $toleranceMinutes))->count();
    }

    private function isLate(AttendanceLog $log, ?Schedule $schedule, int $toleranceMinutes): bool
    {
        $scheduleTime = $this->scheduleTimeForPointType($schedule, $log->point_type);

        if (! $scheduleTime) {
            return false;
        }

        $scheduledAt = Carbon::parse($log->scanned_at->toDateString().' '.$scheduleTime)->addMinutes($toleranceMinutes);

        return $log->scanned_at->greaterThan($scheduledAt);
    }

    private function scheduleTimeForPointType(?Schedule $schedule, string $pointType): ?string
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

    private function resolveTimelinessStatus($scannedAt, ?string $scheduleTime, int $toleranceMinutes): array
    {
        if (! $scheduleTime || ! $scannedAt) {
            return [
                'label' => 'Sudah',
                'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
            ];
        }

        $scheduledAt = Carbon::parse($scannedAt->toDateString().' '.$scheduleTime)->addMinutes($toleranceMinutes);

        if ($scannedAt->greaterThan($scheduledAt)) {
            return [
                'label' => 'Terlambat',
                'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
            ];
        }

        return [
            'label' => 'Tepat Waktu',
            'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyRecapRowsCleaning(Collection $logs, ?CleaningSchedule $schedule, int $toleranceMinutes): array
    {
        $logsByDate = $logs->groupBy(fn (AttendanceLog $log) => $log->scanned_at->toDateString());

        return collect(range(29, 0))
            ->map(function (int $offset) use ($logsByDate, $schedule, $toleranceMinutes): array {
                $date = now()->subDays($offset)->startOfDay();
                $logsForDate = $logsByDate->get($date->toDateString(), collect());

                return [
                    'date' => $date,
                    'total' => $logsForDate->count(),
                    'on_time' => $this->countOnTimeCleaning($logsForDate, $schedule, $toleranceMinutes),
                    'late' => $this->countLateCleaning($logsForDate, $schedule, $toleranceMinutes),
                    'last_scan_at' => $logsForDate->max('scanned_at'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlyRecapRowsCleaning(Collection $logs, ?CleaningSchedule $schedule, int $toleranceMinutes): array
    {
        if ($logs->isEmpty()) {
            return [
                [
                    'month' => now()->startOfMonth(),
                    'total' => 0,
                    'on_time' => 0,
                    'late' => 0,
                    'active_days' => 0,
                ],
            ];
        }

        $logsByMonth = $logs->groupBy(fn (AttendanceLog $log) => $log->scanned_at->format('Y-m'));
        $startMonth = Carbon::parse($logs->first()->scanned_at)->startOfMonth();
        $endMonth = now()->startOfMonth();

        return collect(range(0, $startMonth->diffInMonths($endMonth)))
            ->map(function (int $offset) use ($logsByMonth, $schedule, $toleranceMinutes, $startMonth): array {
                $month = $startMonth->copy()->addMonthsNoOverflow($offset);
                $logsForMonth = $logsByMonth->get($month->format('Y-m'), collect());

                return [
                    'month' => $month,
                    'total' => $logsForMonth->count(),
                    'on_time' => $this->countOnTimeCleaning($logsForMonth, $schedule, $toleranceMinutes),
                    'late' => $this->countLateCleaning($logsForMonth, $schedule, $toleranceMinutes),
                    'active_days' => $logsForMonth->groupBy(fn (AttendanceLog $log) => $log->scanned_at->toDateString())->count(),
                ];
            })
            ->all();
    }

    private function countOnTimeCleaning(Collection $logs, ?CleaningSchedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(fn (AttendanceLog $log): bool => ! $this->isLateCleaning($log, $schedule, $toleranceMinutes))->count();
    }

    private function countLateCleaning(Collection $logs, ?CleaningSchedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(fn (AttendanceLog $log): bool => $this->isLateCleaning($log, $schedule, $toleranceMinutes))->count();
    }

    private function isLateCleaning(AttendanceLog $log, ?CleaningSchedule $schedule, int $toleranceMinutes): bool
    {
        $scheduleTime = $this->scheduleTimeForCleaningPointType($schedule, $log->point_type);

        if (! $scheduleTime) {
            return false;
        }

        $scheduledAt = Carbon::parse($log->scanned_at->toDateString().' '.$scheduleTime)->addMinutes($toleranceMinutes);

        return $log->scanned_at->greaterThan($scheduledAt);
    }

    private function scheduleTimeForCleaningPointType(?CleaningSchedule $schedule, string $pointType): ?string
    {
        return match ($pointType) {
            'CLEANING_CHECKIN' => $schedule?->checkin_time,
            'CLEANING_BREAK_IN' => $schedule?->break_in_time,
            'CLEANING_BREAK_OUT' => $schedule?->break_out_time,
            'CLEANING_CHECKOUT' => $schedule?->checkout_time,
            default => null,
        };
    }

    private function cleaningPointLabel(string $pointType): string
    {
        return match ($pointType) {
            'CLEANING_CHECKIN' => 'Checkin',
            'CLEANING_BREAK_IN' => 'Istirahat IN',
            'CLEANING_BREAK_OUT' => 'Istirahat OUT',
            'CLEANING_CHECKOUT' => 'Checkout',
            default => $pointType,
        };
    }
}
