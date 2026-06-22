<?php

namespace Tests\Feature;

use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\CleaningSchedule;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_cleaning_attendance_report_page(): void
    {
        $admin = $this->createAdminUser();
        $cleaningUser = $this->createCleaningUser();

        $month = now()->subMonthsNoOverflow(1)->startOfMonth();

        $this->seedAttendance($cleaningUser, $month, 'CLEANING_CHECKIN', 'CLEAN-REPORT-001');

        $response = $this->actingAs($admin)->get(route('cleaning-attendance-reports.index', [
            'cleaning_id' => $cleaningUser->id,
            'month' => $month->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertSee('CETAK REKAP KEBERSIHAN');
        $response->assertSee($cleaningUser->name);
    }

    public function test_admin_can_download_cleaning_attendance_report_pdf(): void
    {
        $admin = $this->createAdminUser();
        $cleaningUser = $this->createCleaningUser();

        $month = now()->subMonthsNoOverflow(1)->startOfMonth();

        $this->seedAttendance($cleaningUser, $month, 'CLEANING_CHECKIN', 'CLEAN-REPORT-002');

        $response = $this->actingAs($admin)->get(route('cleaning-attendance-reports.download', [
            'cleaning_id' => $cleaningUser->id,
            'month' => $month->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin'],
        );

        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        return $admin;
    }

    private function createCleaningUser(): User
    {
        $cleaningRole = Role::query()->firstOrCreate(
            ['name' => 'kebersihan'],
            ['display_name' => 'Kebersihan'],
        );

        $cleaningUser = User::factory()->create();
        $cleaningUser->addRole($cleaningRole);

        CleaningSchedule::query()->create([
            'user_id' => $cleaningUser->id,
            'checkin_time' => '08:00',
            'break_in_time' => '12:00',
            'break_out_time' => '13:00',
            'checkout_time' => '17:00',
        ]);

        return $cleaningUser;
    }

    private function seedAttendance(User $cleaningUser, Carbon $month, string $pointType, string $token): void
    {
        $scannedAt = $month->copy()->startOfMonth()->setTime(8, 0);
        $cycle = AttendanceCycle::query()->create([
            'user_id' => $cleaningUser->id,
            'cycle_date' => $scannedAt->toDateString(),
            'started_at' => $scannedAt,
            'expected_end_at' => $scannedAt->copy()->setTime(17, 0),
            'ended_at' => $scannedAt->copy()->setTime(17, 0),
            'status' => AttendanceCycle::STATUS_CLOSED,
            'checkout_mode' => AttendanceCycle::CHECKOUT_MODE_MANUAL,
        ]);

        $qrSet = QrSet::query()->create([
            'code' => 'QR-'.$token,
            'token_prefix' => strtoupper(str_pad(substr($token, 0, 18), 18, 'X')),
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $point = $qrSet->points()->create([
            'point_type' => $pointType,
            'token' => $token,
        ]);

        AttendanceLog::query()->create([
            'attendance_cycle_id' => $cycle->id,
            'user_id' => $cleaningUser->id,
            'qr_set_id' => $qrSet->id,
            'qr_set_point_id' => $point->id,
            'window_group' => $pointType,
            'point_type' => $pointType,
            'token' => $token,
            'scanned_at' => $scannedAt,
            'status' => 'accepted',
            'reason' => null,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);
    }
}
