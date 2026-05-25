<?php

namespace App\Http\Controllers;

use App\Models\QrSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;
use function Spatie\LaravelPdf\Support\pdf;

class QrSetManagementController extends Controller
{
    public function index(): View
    {
        return view('qr-sets.index', [
            'qrSets' => QrSet::query()
                ->with(['points', 'generator.roles'])
                ->orderByDesc('id')
                ->paginate(10),
        ]);
    }

    public function store(): RedirectResponse
    {
        DB::transaction(function (): void {
            $hasActiveSet = QrSet::query()->where('is_active', true)->exists();

            $qrSet = QrSet::query()->create([
                'code' => $this->generateSetCode(),
                'token_prefix' => Str::upper(Str::random(40)),
                'is_active' => ! $hasActiveSet,
                'activated_at' => $hasActiveSet ? null : now(),
                'generated_by' => auth()->id(),
            ]);

            foreach (QrSet::POINT_TYPES as $pointType) {
                $qrSet->points()->create([
                    'point_type' => $pointType,
                    'token' => Str::upper(Str::random(48)),
                ]);
            }
        });

        return redirect()
            ->route('qr-sets.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Satu set QR berhasil dibuat (5 titik).',
            ]);
    }

    public function activate(QrSet $qrSet): RedirectResponse
    {
        DB::transaction(function () use ($qrSet): void {
            QrSet::query()->where('is_active', true)->update([
                'is_active' => false,
                'activated_at' => null,
            ]);

            $qrSet->update([
                'is_active' => true,
                'activated_at' => now(),
            ]);
        });

        return redirect()
            ->route('qr-sets.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Set QR berhasil diaktifkan.',
            ]);
    }

    public function download(QrSet $qrSet)
    {
        $qrSet->load('points');

        if (! $this->isPdfDriverReady()) {
            return redirect()
                ->route('qr-sets.index')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'PDF belum bisa dibuat. Konfigurasi driver PDF belum lengkap.',
                ]);
        }

        $points = $qrSet->points
            ->sortBy(fn ($point) => array_search($point->point_type, QrSet::POINT_TYPES, true))
            ->map(function ($point) {
                $scanUrl = $this->buildScanUrl($point->token, $point->point_type);

                return [
                    'point_type' => $point->point_type,
                    'label' => $this->pointLabel($point->point_type),
                    'token' => $point->token,
                    'scan_url' => $scanUrl,
                    'image_url' => 'https://quickchart.io/qr?size=520&margin=1&text='.urlencode($scanUrl),
                ];
            })
            ->values();

        try {
            return pdf()
                ->view('pdf.qr-set', [
                    'qrSet' => $qrSet,
                    'points' => $points,
                ])
                ->name('qr-set-'.$qrSet->code.'.pdf')
                ->download();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('qr-sets.index')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'PDF belum bisa dibuat. Konfigurasi driver PDF belum lengkap.',
                ]);
        }
    }

    private function generateSetCode(): string
    {
        return 'QR-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
    }

    private function buildScanUrl(string $token, string $pointType): string
    {
        $baseUrl = rtrim((string) (config('app.url') ?: url('/')), '/');

        return $baseUrl.'/absen/'.$token.'/'.$pointType;
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

    private function isPdfDriverReady(): bool
    {
        $driver = (string) config('laravel-pdf.driver', 'browsershot');

        return match ($driver) {
            'browsershot' => class_exists('Spatie\\Browsershot\\Browsershot'),
            'dompdf' => class_exists('Dompdf\\Dompdf'),
            'chrome' => class_exists('HeadlessChromium\\BrowserFactory'),
            default => true,
        };
    }
}
