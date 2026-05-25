<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $fillable = [
        'attendance_cycle_id',
        'user_id',
        'qr_set_id',
        'qr_set_point_id',
        'window_group',
        'point_type',
        'token',
        'scanned_at',
        'status',
        'reason',
        'latitude',
        'longitude',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceCycle(): BelongsTo
    {
        return $this->belongsTo(AttendanceCycle::class);
    }

    public function qrSet(): BelongsTo
    {
        return $this->belongsTo(QrSet::class);
    }

    public function qrSetPoint(): BelongsTo
    {
        return $this->belongsTo(QrSetPoint::class);
    }
}
