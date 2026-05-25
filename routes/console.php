<?php

use App\Models\AttendanceCycle;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    AttendanceCycle::expireOpenCycles();
})
    ->name('attendance-cycles:auto-expire')
    ->everyMinute()
    ->withoutOverlapping();
