<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCleaningAttendanceReportRequest;
use App\Models\AppSetting;
use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\CleaningSchedule;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

use function Spatie\LaravelPdf\Support\pdf;

class CleaningAttendanceReportController extends Controller
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
        $cleaningUsers = $this->cleaningOptions();
        $selectedCleaning = $cleaningUsers->firstWhere('id', (int) $request->integer('cleaning_id')) ?? $cleaningUsers->first();
        $monthOptions = $this->monthOptions($selectedCleaning?->id);
        $selectedMonth = $this->resolveSelectedMonth($monthOptions, $request->string('month')->toString());
        $report = $selectedCleaning && $selectedMonth ? $this->buildReportData($selectedCleaning, $selectedMonth) : null;

        return view('cleaning-attendance-reports.index', [
            'cleaningUsers' => $cleaningUsers,
            'monthOptions' => $monthOptions,
            'selectedCleaning' => $selectedCleaning,
            'selectedMonth' => $selectedMonth,
            'report' => $report,
        ]);
    }

    public function download(GenerateCleaningAttendanceReportRequest $request): mixed
    {
        $cleaningUser = $this->cleaningOptions()->firstWhere('id', (int) $request->validated('cleaning_id'));

        if (! $cleaningUser) {
            abort(404);
        }

        $month = Carbon::createFromFormat('Y-m', $request->validated('month'))->startOfMonth();
        $report = $this->buildReportData($cleaningUser, $month);

        try {
            return pdf()
                ->view('pdf.cleaning-attendance-report', $report)
                ->name('rekap-kebersihan-'.$cleaningUser->id.'-'.$month->format('Y-m').'.pdf')
                ->landscape()
                ->download();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('cleaning-attendance-reports.index', [
                    'cleaning_id' => $cleaningUser->id,
                    'month' => $month->format('Y-m'),
                ])
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'PDF belum bisa dibuat. Konfigurasi driver PDF belum lengkap.',
                ]);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function cleaningOptions(): Collection
    {
        return User::query()
            ->with(['cleaningSchedule', 'roles'])
            ->whereHas('roles', fn ($query) => $query->where('name', 'kebersihan'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function monthOptions(?int $cleaningUserId = null): Collection
    {
        $query = AttendanceCycle::query();

        if ($cleaningUserId) {
            $query->where('user_id', $cleaningUserId);
        }

        return $query
            ->selectRaw("strftime('%Y-%m', cycle_date) as month_value", [])
            ->distinct()
            ->pluck('month_value')
            ->filter()
            ->sort()
            ->values()
            ->map(fn (string $monthValue): array => [
                'value' => $monthValue,
                'label' => Carbon::createFromFormat('Y-m', $monthValue)->translatedFormat('F Y'),
            ]);
    }

    /**
     * @param  Collection<int, array{value: string, label: string}>  $monthOptions
     */
    private function resolveSelectedMonth(Collection $monthOptions, string $monthValue): ?Carbon
    {
        if ($monthValue !== '' && $monthOptions->firstWhere('value', $monthValue)) {
            return Carbon::createFromFormat('Y-m', $monthValue)->startOfMonth();
        }

        $latest = $monthOptions->last();

        return $latest ? Carbon::createFromFormat('Y-m', $latest['value'])->startOfMonth() : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(User $cleaningUser, Carbon $month): array
    {
        AttendanceCycle::expireOpenCycles($cleaningUser->id);
        $cleaningUser->loadMissing('cleaningSchedule');
        $schedule = $cleaningUser->cleaningSchedule;
        $toleranceMinutes = (int) (AppSetting::current()->late_tolerance_minutes ?? 0);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $cycles = AttendanceCycle::query()
            ->with([
                'attendanceLogs' => fn ($query) => $query
                    ->where('status', 'accepted')
                    ->whereIn('point_type', self::POINT_TYPES)
                    ->orderBy('scanned_at'),
            ])
            ->where('user_id', $cleaningUser->id)
            ->whereBetween('cycle_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('cycle_date')
            ->get();

        $logs = $cycles->flatMap(fn (AttendanceCycle $cycle) => $cycle->attendanceLogs)->values();
        $cycleLookup = $cycles->keyBy(fn (AttendanceCycle $cycle) => $cycle->cycle_date->format('Y-m-d'));

        $dailyRows = collect(CarbonPeriod::create($start, $end))
            ->map(function (Carbon $date) use ($cycleLookup, $schedule, $toleranceMinutes): array {
                /** @var AttendanceCycle|null $cycle */
                $cycle = $cycleLookup->get($date->format('Y-m-d'));
                $logsForDate = $cycle?->attendanceLogs ?? collect();
                $dateCycles = $cycle ? collect([$cycle]) : collect();

                return [
                    'date' => $date,
                    'total' => $logsForDate->count(),
                    'on_time' => $this->countOnTime($logsForDate, $dateCycles, $schedule, $toleranceMinutes),
                    'late' => $this->countLate($logsForDate, $dateCycles, $schedule, $toleranceMinutes),
                    'first_scan_at' => $logsForDate->min('scanned_at'),
                    'last_scan_at' => $logsForDate->max('scanned_at'),
                ];
            })
            ->values();

        $dailyPointColumns = collect(self::POINT_TYPES)
            ->map(fn (string $pointType): array => [
                'point_type' => $pointType,
                'label' => $this->pointLabel($pointType),
                'schedule_time' => $this->scheduleTimeForPointType($schedule, $pointType),
            ])
            ->values();

        $dailyPointRows = collect(CarbonPeriod::create($start, $end))
            ->map(function (Carbon $date) use ($dailyPointColumns, $cycleLookup, $schedule, $toleranceMinutes): array {
                /** @var AttendanceCycle|null $cycle */
                $cycle = $cycleLookup->get($date->format('Y-m-d'));
                $cycleLogs = $cycle?->attendanceLogs?->keyBy('point_type') ?? collect();

                $points = $dailyPointColumns->map(function (array $column) use ($cycle, $cycleLogs, $schedule, $toleranceMinutes): array {
                    /** @var AttendanceLog|null $log */
                    $log = $cycleLogs->get($column['point_type']);

                    if (! $log) {
                        return [
                            'point_type' => $column['point_type'],
                            'scanned_at' => null,
                            'display_time' => $cycle ? 'Tidak Absen' : '-',
                            'status_label' => $cycle ? 'Tidak Absen' : 'Belum',
                            'status_category' => $cycle ? 'missed' : 'empty',
                        ];
                    }

                    if ($log->reason === 'expired') {
                        return [
                            'point_type' => $column['point_type'],
                            'scanned_at' => $log->scanned_at,
                            'display_time' => $log->scanned_at->format('H:i'),
                            'status_label' => 'Expired',
                            'status_category' => 'expired',
                        ];
                    }

                    $isLate = $cycle ? $this->isLate($log, $cycle, $schedule, $toleranceMinutes) : false;

                    return [
                        'point_type' => $column['point_type'],
                        'scanned_at' => $log->scanned_at,
                        'display_time' => $log->scanned_at->format('H:i'),
                        'status_label' => $isLate ? 'Terlambat' : 'Tepat Waktu',
                        'status_category' => $isLate ? 'late' : 'on_time',
                    ];
                })->values();

                return [
                    'date' => $date,
                    'points' => $points,
                ];
            })
            ->values();

        $pointRows = collect(self::POINT_TYPES)
            ->map(function (string $pointType) use ($logs, $cycles, $schedule, $toleranceMinutes): array {
                $pointLogs = $logs->where('point_type', $pointType)->values();

                return [
                    'label' => $this->pointLabel($pointType),
                    'schedule_time' => $this->scheduleTimeForPointType($schedule, $pointType),
                    'total' => $pointLogs->count(),
                    'on_time' => $this->countOnTime($pointLogs, $cycles, $schedule, $toleranceMinutes),
                    'late' => $this->countLate($pointLogs, $cycles, $schedule, $toleranceMinutes),
                    'last_scan_at' => $pointLogs->max('scanned_at'),
                ];
            })
            ->values();

        return [
            'cleaningUser' => $cleaningUser,
            'month' => $month,
            'monthLabel' => $month->translatedFormat('F Y'),
            'reportGeneratedAt' => now(),
            'summaryCards' => [
                [
                    'label' => 'Total Absen',
                    'value' => $logs->count().' kali',
                    'description' => 'Jumlah absensi accepted pada siklus bulan terpilih.',
                ],
                [
                    'label' => 'Tepat Waktu',
                    'value' => $this->countOnTime($logs, $cycles, $schedule, $toleranceMinutes).' kali',
                    'description' => 'Absensi yang masih dalam toleransi waktu.',
                ],
                [
                    'label' => 'Terlambat',
                    'value' => $this->countLate($logs, $cycles, $schedule, $toleranceMinutes).' kali',
                    'description' => 'Absensi yang melewati batas toleransi.',
                ],
                [
                    'label' => 'Hari Aktif',
                    'value' => $cycles->count().' siklus',
                    'description' => 'Jumlah siklus absensi yang dimulai pada bulan terpilih.',
                ],
            ],
            'pointRows' => $pointRows,
            'dailyRows' => $dailyRows,
            'dailyPointColumns' => $dailyPointColumns,
            'dailyPointRows' => $dailyPointRows,
            'schedule' => $schedule,
            'toleranceMinutes' => $toleranceMinutes,
        ];
    }

    private function countOnTime(Collection $logs, Collection $cycles, ?CleaningSchedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(function (AttendanceLog $log) use ($cycles, $schedule, $toleranceMinutes): bool {
            $cycle = $cycles->firstWhere('id', $log->attendance_cycle_id);

            return $cycle && ! $this->isLate($log, $cycle, $schedule, $toleranceMinutes);
        })->count();
    }

    private function countLate(Collection $logs, Collection $cycles, ?CleaningSchedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(function (AttendanceLog $log) use ($cycles, $schedule, $toleranceMinutes): bool {
            $cycle = $cycles->firstWhere('id', $log->attendance_cycle_id);

            return $cycle && $this->isLate($log, $cycle, $schedule, $toleranceMinutes);
        })->count();
    }

    private function isLate(AttendanceLog $log, AttendanceCycle $cycle, ?CleaningSchedule $schedule, int $toleranceMinutes): bool
    {
        $scheduleTime = $this->scheduleTimeForPointType($schedule, $log->point_type);

        if (! $scheduleTime) {
            return false;
        }

        $scheduledAt = Carbon::parse($cycle->cycle_date->format('Y-m-d').' '.$scheduleTime);

        if ($schedule?->checkin_time && $scheduleTime < $schedule->checkin_time) {
            $scheduledAt->addDay();
        }

        $scheduledAt->addMinutes($toleranceMinutes);

        return $log->scanned_at->greaterThan($scheduledAt);
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
}
