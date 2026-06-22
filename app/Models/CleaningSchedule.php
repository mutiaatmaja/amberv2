<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningSchedule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'checkin_time',
        'break_in_time',
        'break_out_time',
        'checkout_time',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
