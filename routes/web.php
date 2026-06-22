<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceLogManagementController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CleaningAttendanceController;
use App\Http\Controllers\CleaningAttendanceLogManagementController;
use App\Http\Controllers\CleaningAttendanceReportController;
use App\Http\Controllers\CleaningScanQrController;
use App\Http\Controllers\CleaningScheduleManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardPasswordController;
use App\Http\Controllers\QrSetManagementController;
use App\Http\Controllers\ScanQrController;
use App\Http\Controllers\ScheduleManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Middleware\EnsureAdminOrSupervisor;
use App\Http\Middleware\EnsureKebersihan;
use App\Http\Middleware\EnsureSatpam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::put('/dashboard/password', [DashboardPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('dashboard.password.update');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware([EnsureSatpam::class, 'throttle:20,1'])->group(function (): void {
        Route::get('/scan-qr', ScanQrController::class)->name('scan-qr');
        Route::get('/absen/{token}/{pointType}', [AttendanceController::class, 'show'])->name('attendance.scan');
        Route::post('/absen/{token}/{pointType}', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    Route::middleware([EnsureKebersihan::class, 'throttle:20,1'])->group(function (): void {
        Route::get('/scan-qr-kebersihan', CleaningScanQrController::class)->name('cleaning-scan-qr');
        Route::get('/absen-kebersihan/{token}/{pointType}', [CleaningAttendanceController::class, 'show'])
            ->name('cleaning-attendance.scan');
        Route::post('/absen-kebersihan/{token}/{pointType}', [CleaningAttendanceController::class, 'store'])
            ->name('cleaning-attendance.store');
    });

    Route::middleware(EnsureAdminOrSupervisor::class)->group(function (): void {
        Route::resource('users', UserManagementController::class)
            ->except(['destroy']);

        Route::resource('schedules', ScheduleManagementController::class);
        Route::resource('cleaning-schedules', CleaningScheduleManagementController::class);

        Route::get('rekap-cetak', [AttendanceReportController::class, 'index'])
            ->name('attendance-reports.index');
        Route::get('rekap-cetak/download', [AttendanceReportController::class, 'download'])
            ->name('attendance-reports.download');

        Route::get('rekap-cetak-kebersihan', [CleaningAttendanceReportController::class, 'index'])
            ->name('cleaning-attendance-reports.index');
        Route::get('rekap-cetak-kebersihan/download', [CleaningAttendanceReportController::class, 'download'])
            ->name('cleaning-attendance-reports.download');

        Route::resource('attendance-logs', AttendanceLogManagementController::class)
            ->only(['index', 'edit', 'update']);

        Route::resource('cleaning-attendance-logs', CleaningAttendanceLogManagementController::class)
            ->parameters(['cleaning-attendance-logs' => 'attendanceLog'])
            ->only(['index', 'edit', 'update']);

        Route::get('settings', [AppSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AppSettingController::class, 'update'])->name('settings.update');

        Route::get('qr-sets', [QrSetManagementController::class, 'index'])->name('qr-sets.index');
        Route::post('qr-sets', [QrSetManagementController::class, 'store'])->name('qr-sets.store');
        Route::post('qr-sets/{qrSet}/activate', [QrSetManagementController::class, 'activate'])->name('qr-sets.activate');
        Route::get('qr-sets/{qrSet}/download', [QrSetManagementController::class, 'download'])->name('qr-sets.download');
    });
});
