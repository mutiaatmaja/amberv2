<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'early_tolerance_minutes',
        'late_tolerance_minutes',
        'auto_checkout_grace_minutes',
        'require_gps',
        'show_map',
    ];

    protected function casts(): array
    {
        return [
            'early_tolerance_minutes' => 'integer',
            'late_tolerance_minutes' => 'integer',
            'auto_checkout_grace_minutes' => 'integer',
            'require_gps' => 'boolean',
            'show_map' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'early_tolerance_minutes' => 30,
            'late_tolerance_minutes' => 0,
            'auto_checkout_grace_minutes' => 0,
            'require_gps' => true,
            'show_map' => true,
        ]);
    }
}
