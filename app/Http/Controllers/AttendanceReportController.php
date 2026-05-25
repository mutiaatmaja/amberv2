<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAttendanceReportRequest;
use App\Models\AppSetting;
use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\QrSet;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

use function Spatie\LaravelPdf\Support\pdf;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $satpams = $this->satpamOptions();
        $selectedSatpam = $satpams->firstWhere('id', (int) $request->integer('satpam_id')) ?? $satpams->first();
        $monthOptions = $this->monthOptions($selectedSatpam?->id);
        $selectedMonth = $this->resolveSelectedMonth($monthOptions, $request->string('month')->toString());
        $report = $selectedSatpam && $selectedMonth ? $this->buildReportData($selectedSatpam, $selectedMonth) : null;

        return view('attendance-reports.index', [
            'satpams' => $satpams,
            'monthOptions' => $monthOptions,
            'selectedSatpam' => $selectedSatpam,
            'selectedMonth' => $selectedMonth,
            'report' => $report,
        ]);
    }

    public function download(GenerateAttendanceReportRequest $request): mixed
    {
        $satpam = $this->satpamOptions()->firstWhere('id', (int) $request->validated('satpam_id'));

        if (! $satpam) {
            abort(404);
        }

        $month = Carbon::createFromFormat('Y-m', $request->validated('month'))->startOfMonth();
        $report = $this->buildReportData($satpam, $month);

        try {
            return pdf()
                ->view('pdf.attendance-report', $report)
                ->name('rekap-'.$satpam->id.'-'.$month->format('Y-m').'.pdf')
                ->landscape()
                ->download();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('attendance-reports.index', [
                    'satpam_id' => $satpam->id,
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
    private function satpamOptions(): Collection
    {
        return User::query()
            ->with(['schedule', 'roles'])
            ->whereHas('roles', fn ($query) => $query->where('name', 'satpam'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function monthOptions(?int $satpamId = null): Collection
    {
        $query = AttendanceCycle::query();

        if ($satpamId) {
            $query->where('user_id', $satpamId);
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
    private function buildReportData(User $satpam, Carbon $month): array
    {
        AttendanceCycle::expireOpenCycles($satpam->id);
        $satpam->loadMissing('schedule');
        $schedule = $satpam->schedule;
        $toleranceMinutes = (int) (AppSetting::current()->late_tolerance_minutes ?? 0);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $cycles = AttendanceCycle::query()
            ->with([
                'attendanceLogs' => fn ($query) => $query
                    ->where('status', 'accepted')
                    ->orderBy('scanned_at'),
            ])
            ->where('user_id', $satpam->id)
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

        $dailyPointColumns = collect(QrSet::POINT_TYPES)
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

        $pointRows = collect(QrSet::POINT_TYPES)
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
            'satpam' => $satpam,
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

    private function countOnTime(Collection $logs, Collection $cycles, ?Schedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(function (AttendanceLog $log) use ($cycles, $schedule, $toleranceMinutes): bool {
            $cycle = $cycles->firstWhere('id', $log->attendance_cycle_id);

            return $cycle && ! $this->isLate($log, $cycle, $schedule, $toleranceMinutes);
        })->count();
    }

    private function countLate(Collection $logs, Collection $cycles, ?Schedule $schedule, int $toleranceMinutes): int
    {
        return $logs->filter(function (AttendanceLog $log) use ($cycles, $schedule, $toleranceMinutes): bool {
            $cycle = $cycles->firstWhere('id', $log->attendance_cycle_id);

            return $cycle && $this->isLate($log, $cycle, $schedule, $toleranceMinutes);
        })->count();
    }

    private function isLate(AttendanceLog $log, AttendanceCycle $cycle, ?Schedule $schedule, int $toleranceMinutes): bool
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
}
