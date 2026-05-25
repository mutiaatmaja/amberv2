<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrSet extends Model
{
    public const POINT_TYPES = [
        'CHECKIN',
        'PATROL_1',
        'STANDBY_1',
        'PATROL_2',
        'STANDBY_2',
        'CHECKOUT',
    ];

    protected $fillable = [
        'code',
        'token_prefix',
        'is_active',
        'activated_at',
        'generated_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    public function points(): HasMany
    {
        return $this->hasMany(QrSetPoint::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
