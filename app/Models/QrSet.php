<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class QrSet extends Model
{
    public const POINT_TYPES = [
        'CHECKIN',
        'CHECKOUT',
        'PATROL_A',
        'PATROL_B',
        'PATROL_C',
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
