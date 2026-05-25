<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\QrSet;
use App\Models\QrSetPoint;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SatpamDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_satpam_can_view_dashboard_sections_and_monthly_stats(): void
    {
        $satpam = $this->seedSatpamUserWithSchedule();

        $todayPoint = $this->createPointForLog('CHECKIN', 'SATPAM-DASH-001');
        $latePoint = $this->createPointForLog('PATROL_1', 'SATPAM-DASH-002');

        AttendanceLog::query()->create([
            'user_id' => $satpam->id,
            'qr_set_id' => $todayPoint->qr_set_id,
            'qr_set_point_id' => $todayPoint->id,
            'point_type' => 'CHECKIN',
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
            'user_id' => $satpam->id,
            'qr_set_id' => $latePoint->qr_set_id,
            'qr_set_point_id' => $latePoint->id,
            'point_type' => 'PATROL_1',
            'token' => $latePoint->token,
            'scanned_at' => today()->subDays(3)->setTime(9, 30),
            'status' => 'accepted',
            'reason' => null,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($satpam)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Satpam');
        $response->assertSee('Proses Absen Hari Ini');
        $response->assertSee('Rekap Harian');
        $response->assertSee('Rekap Bulanan');
        $response->assertSee('Pengaturan');
        $response->assertSee('Total Absen 1 Bulan');
        $response->assertSee('2 kali');
        $response->assertSee('1 kali');
        $response->assertSee('Terlambat');
    }

    public function test_monthly_recap_starts_from_first_attendance_month(): void
    {
        $satpam = $this->seedSatpamUserWithSchedule();

        $oldPoint = $this->createPointForLog('CHECKIN', 'SATPAM-DASH-OLD');
        $currentPoint = $this->createPointForLog('CHECKIN', 'SATPAM-DASH-CURRENT');

        AttendanceLog::query()->create([
            'user_id' => $satpam->id,
            'qr_set_id' => $oldPoint->qr_set_id,
            'qr_set_point_id' => $oldPoint->id,
            'point_type' => 'CHECKIN',
            'token' => $oldPoint->token,
            'scanned_at' => now()->subMonthsNoOverflow(2)->startOfMonth()->setTime(8, 0),
            'status' => 'accepted',
            'reason' => null,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        AttendanceLog::query()->create([
            'user_id' => $satpam->id,
            'qr_set_id' => $currentPoint->qr_set_id,
            'qr_set_point_id' => $currentPoint->id,
            'point_type' => 'CHECKIN',
            'token' => $currentPoint->token,
            'scanned_at' => now()->setTime(8, 0),
            'status' => 'accepted',
            'reason' => null,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($satpam)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(now()->subMonthsNoOverflow(2)->startOfMonth()->translatedFormat('F Y'));
    }

    public function test_satpam_can_update_dashboard_password(): void
    {
        $satpam = $this->seedSatpamUserWithSchedule();

        $response = $this->actingAs($satpam)->put(route('dashboard.password.update'), [
            'current_password' => 'password',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('toast.message', 'Password berhasil diperbarui.');

        $satpam->refresh();

        $this->assertTrue(Hash::check('password-baru-123', $satpam->password));
    }

    public function test_satpam_cannot_update_dashboard_password_with_wrong_current_password(): void
    {
        $satpam = $this->seedSatpamUserWithSchedule();

        $response = $this->actingAs($satpam)->put(route('dashboard.password.update'), [
            'current_password' => 'salah',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    private function seedSatpamUserWithSchedule(): User
    {
        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

        $satpam = User::factory()->create([
            'password' => 'password',
        ]);
        $satpam->addRole($satpamRole);

        Schedule::query()->create([
            'user_id' => $satpam->id,
            'checkin_time' => '08:00',
            'checkout_time' => '17:00',
            'patrol_1_time' => '09:00',
            'standby_1_time' => '10:00',
            'patrol_2_time' => '13:00',
            'standby_2_time' => '14:00',
            'patrol_a_time' => '09:00',
            'patrol_b_time' => '10:00',
            'patrol_c_time' => '11:00',
        ]);

        return $satpam;
    }

    private function createPointForLog(string $pointType, string $token): QrSetPoint
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
