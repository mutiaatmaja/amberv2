<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [
        'user_id',
        'checkin_time',
        'checkout_time',
        'patrol_1_time',
        'standby_1_time',
        'patrol_2_time',
        'standby_2_time',
        'patrol_a_time',
        'patrol_b_time',
        'patrol_c_time',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
