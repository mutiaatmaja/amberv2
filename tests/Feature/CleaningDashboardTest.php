<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\CleaningSchedule;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaning_user_can_view_dashboard_sections_and_monthly_stats(): void
    {
        $cleaningUser = $this->seedCleaningUserWithSchedule();

        $todayPoint = $this->createPointForLog('CLEANING_CHECKIN', 'CLEAN-DASH-001');
        $latePoint = $this->createPointForLog('CLEANING_BREAK_IN', 'CLEAN-DASH-002');

        AttendanceLog::query()->create([
            'user_id' => $cleaningUser->id,
            'qr_set_id' => $todayPoint->qr_set_id,
            'qr_set_point_id' => $todayPoint->id,
            'point_type' => 'CLEANING_CHECKIN',
            'token' => $todayPoint->token,
            'scanned_at' => today()->setTime(8, 0),
            'status' => 'accepted',
            'reason' => null,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        AttendanceLog::query()->create([
            'user_id' => $cleaningUser->id,
            'qr_set_id' => $latePoint->qr_set_id,
            'qr_set_point_id' => $latePoint->id,
            'point_type' => 'CLEANING_BREAK_IN',
            'token' => $latePoint->token,
            'scanned_at' => today()->subDays(3)->setTime(12, 30),
            'status' => 'accepted',
            'reason' => null,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($cleaningUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Kebersihan');
        $response->assertSee('Proses Absen Hari Ini');
        $response->assertSee('Rekap Harian');
        $response->assertSee('Rekap Bulanan');
        $response->assertSee('Pengaturan');
        $response->assertSee('Total Absen 1 Bulan');
        $response->assertSee('2 kali');
        $response->assertSee('1 kali');
        $response->assertSee('Terlambat');
        $response->assertSee('Istirahat IN');
    }

    private function seedCleaningUserWithSchedule(): User
    {
        $cleaningRole = Role::query()->firstOrCreate(
            ['name' => 'kebersihan'],
            ['display_name' => 'Kebersihan'],
        );

        $cleaningUser = User::factory()->create([
            'password' => 'password',
        ]);
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

    private function createPointForLog(string $pointType, string $token): object
    {
        $qrSet = QrSet::query()->create([
            'code' => $token,
            'token_prefix' => str_pad(substr($token, 0, 18), 18, 'X'),
            'is_active' => true,
            'activated_at' => now(),
        ]);

        return $qrSet->points()->create([
            'point_type' => $pointType,
            'token' => $token,
        ]);
    }
}
