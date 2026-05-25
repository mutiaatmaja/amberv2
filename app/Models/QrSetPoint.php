<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class QrSetPoint extends Model
{
    protected $fillable = [
        'qr_set_id',
        'point_type',
        'token',
    ];

    public function qrSet(): BelongsTo
    {
        return $this->belongsTo(QrSet::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
