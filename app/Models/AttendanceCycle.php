<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AttendanceCycle extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_EXPIRED = 'expired';

    public const CHECKOUT_MODE_MANUAL = 'manual';

    public const CHECKOUT_MODE_AUTO = 'auto';

    protected $fillable = [
        'user_id',
        'cycle_date',
        'started_at',
        'expected_end_at',
        'ended_at',
        'status',
        'checkout_mode',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cycle_date' => 'date',
            'started_at' => 'datetime',
            'expected_end_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public static function expireOpenCycles(?int $userId = null): void
    {
        $query = self::query()
            ->with('attendanceLogs')
            ->open()
            ->where('expected_end_at', '<=', now())
            ->orderBy('expected_end_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query->get()->each(function (self $cycle): void {
            DB::transaction(function () use ($cycle): void {
                $cycle->refresh();

                if ($cycle->status !== self::STATUS_OPEN) {
                    return;
                }

                $hasCheckout = $cycle->attendanceLogs()
                    ->where('point_type', 'CHECKOUT')
                    ->where('status', 'accepted')
                    ->exists();

                if (! $hasCheckout) {
                    AttendanceLog::query()->create([
                        'attendance_cycle_id' => $cycle->id,
                        'user_id' => $cycle->user_id,
                        'qr_set_id' => null,
                        'qr_set_point_id' => null,
                        'window_group' => 'CHECKOUT',
                        'point_type' => 'CHECKOUT',
                        'token' => 'AUTO-CHECKOUT-'.$cycle->id,
                        'scanned_at' => $cycle->expected_end_at,
                        'status' => 'accepted',
                        'reason' => 'expired',
                        'latitude' => null,
                        'longitude' => null,
                        'ip_address' => null,
                        'user_agent' => 'system:auto-checkout',
                    ]);
                }

                $cycle->forceFill([
                    'ended_at' => $cycle->expected_end_at,
                    'status' => self::STATUS_EXPIRED,
                    'checkout_mode' => self::CHECKOUT_MODE_AUTO,
                    'notes' => 'Auto checkout because the cycle expired.',
                ])->save();
            });
        });
    }
}
