<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'user_id',
        'qr_set_id',
        'qr_set_point_id',
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

    public function qrSet(): BelongsTo
    {
        return $this->belongsTo(QrSet::class);
    }

    public function qrSetPoint(): BelongsTo
    {
        return $this->belongsTo(QrSetPoint::class);
    }
}
