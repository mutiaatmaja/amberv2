<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'late_tolerance_minutes',
        'require_gps',
        'show_map',
    ];

    protected function casts(): array
    {
        return [
            'late_tolerance_minutes' => 'integer',
            'require_gps' => 'boolean',
            'show_map' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'late_tolerance_minutes' => 0,
            'require_gps' => true,
            'show_map' => true,
        ]);
    }
}
