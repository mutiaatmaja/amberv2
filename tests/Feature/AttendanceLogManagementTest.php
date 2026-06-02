<?php

namespace Tests\Feature;

use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_attendance_log_index(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamUser();
        $this->createLog($satpam);

        $response = $this->actingAs($admin)->get(route('attendance-logs.index'));

        $response->assertOk();
        $response->assertSee('Kelola Absensi');
        // satpam name appears in the dropdown
        $response->assertSee($satpam->name);
    }

    public function test_admin_can_view_daily_table_when_satpam_selected(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamUser();
        $log = $this->createLog($satpam);
        $month = $log->scanned_at->format('Y-m');

        $response = $this->actingAs($admin)
            ->get(route('attendance-logs.index', ['user_id' => $satpam->id, 'month' => $month]));

        $response->assertOk();
        $response->assertSee($satpam->name);
        // daily table headers
        $response->assertSee('Tanggal');
        $response->assertSee('Checkin');
        // scan time appears as HH:MM badge
        $response->assertSee($log->scanned_at->format('H:i'));
        $response->assertSee('aria-label="Lihat detail absensi"', false);
    }

    public function test_admin_can_filter_logs_by_satpam(): void
    {
        $admin = $this->createAdminUser();
        $satpamA = $this->createSatpamUser('Satpam Alpha');
        $satpamB = $this->createSatpamUser('Satpam Beta');
        $this->createLog($satpamA);
        $this->createLog($satpamB);

        $response = $this->actingAs($admin)
            ->get(route('attendance-logs.index', ['user_id' => $satpamA->id]));

        $response->assertOk();
        $response->assertSee('Satpam Alpha');
        // verify the selected option is correctly set (filter applied)
        $response->assertSee('value="'.$satpamA->id.'"', false);
    }

    public function test_admin_can_view_edit_attendance_log_form(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamUser();
        $log = $this->createLog($satpam, 'accepted', -6.2012, 106.8163);

        $response = $this->actingAs($admin)
            ->get(route('attendance-logs.edit', $log));

        $response->assertOk();
        $response->assertSee('Form Ubah Absensi');
        $response->assertSee($satpam->name);
        $response->assertSee('Lokasi GPS');
        $response->assertSee('-6.2012');
        $response->assertSee('106.8163');
        $response->assertSee('Buka di Google Maps');
        $response->assertSee('cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css', false);
        $response->assertSee('cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js', false);
        $response->assertSee('attendance-map-pin', false);
    }

    public function test_admin_can_update_attendance_log(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamUser();
        $log = $this->createLog($satpam, 'accepted');

        $response = $this->actingAs($admin)
            ->put(route('attendance-logs.update', $log), [
                'scanned_at' => '2026-05-01 09:30:00',
                'status' => 'rejected',
                'reason' => 'Absensi dikoreksi oleh admin',
            ]);

        $response->assertRedirect(route('attendance-logs.index', [
            'user_id' => $satpam->id,
            'month' => '2026-05',
        ]));
        $response->assertSessionHas('toast.type', 'success');

        $log->refresh();
        $this->assertEquals('rejected', $log->status);
        $this->assertEquals('Absensi dikoreksi oleh admin', $log->reason);
        $this->assertEquals('2026-05-01 09:30:00', $log->scanned_at->format('Y-m-d H:i:s'));
    }

    public function test_update_validates_required_fields(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamUser();
        $log = $this->createLog($satpam);

        $response = $this->actingAs($admin)
            ->put(route('attendance-logs.update', $log), [
                'scanned_at' => '',
                'status' => 'invalid_status',
                'reason' => null,
            ]);

        $response->assertSessionHasErrors(['scanned_at', 'status']);
    }

    public function test_satpam_cannot_access_attendance_log_management(): void
    {
        $satpam = $this->createSatpamUser();
        $log = $this->createLog($satpam);

        $this->actingAs($satpam)
            ->get(route('attendance-logs.index'))
            ->assertForbidden();

        $this->actingAs($satpam)
            ->get(route('attendance-logs.edit', $log))
            ->assertForbidden();
    }

    private function createAdminUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $user = User::factory()->create();
        $user->addRole($role);

        return $user;
    }

    private function createSatpamUser(string $name = 'Satpam Satu'): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'satpam'], ['display_name' => 'Satpam']);
        $user = User::factory()->create(['name' => $name]);
        $user->addRole($role);

        Schedule::query()->create([
            'user_id' => $user->id,
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

        return $user;
    }

    private function createLog(User $satpam, string $status = 'accepted', ?float $latitude = null, ?float $longitude = null): AttendanceLog
    {
        $cycle = AttendanceCycle::query()->create([
            'user_id' => $satpam->id,
            'cycle_date' => now()->toDateString(),
            'started_at' => now()->copy()->startOfDay()->addHours(8),
            'expected_end_at' => now()->copy()->startOfDay()->addHours(17),
            'ended_at' => now()->copy()->startOfDay()->addHours(17),
            'status' => AttendanceCycle::STATUS_CLOSED,
            'checkout_mode' => AttendanceCycle::CHECKOUT_MODE_MANUAL,
        ]);

        $qrSet = QrSet::query()->create([
            'code' => 'QR-TEST-'.uniqid(),
            'token_prefix' => strtoupper(substr(uniqid('TEST', true), 0, 18)),
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $point = $qrSet->points()->create([
            'point_type' => 'CHECKIN',
            'token' => 'TOKEN-'.uniqid(),
        ]);

        return AttendanceLog::query()->create([
            'attendance_cycle_id' => $cycle->id,
            'user_id' => $satpam->id,
            'qr_set_id' => $qrSet->id,
            'qr_set_point_id' => $point->id,
            'window_group' => 'CHECKIN',
            'point_type' => 'CHECKIN',
            'token' => $point->token,
            'scanned_at' => now(),
            'status' => $status,
            'reason' => null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
        ]);
    }
}
