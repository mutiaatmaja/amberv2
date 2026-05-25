<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{admin: User, satpam: User}
     */
    private function seedUsersWithRoles(): array
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        /** @var User $satpam */
        $satpam = User::factory()->create();
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

        return [
            'admin' => $admin,
            'satpam' => $satpam,
        ];
    }

    private function createActiveCheckinPoint(): array
    {
        $qrSet = QrSet::query()->create([
            'code' => 'QR-TEST-001',
            'token_prefix' => 'TOKENPREFIXTEST001',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $point = $qrSet->points()->create([
            'point_type' => 'CHECKIN',
            'token' => 'SFVR3I6AVEQW2QDEBNS6DGMJPPLFN8E1TVLQEQNWGA1SDOIO',
        ]);

        return [
            'set' => $qrSet,
            'point' => $point,
        ];
    }

    public function test_satpam_can_open_scan_page(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        $response = $this->actingAs($users['satpam'])->get(route('attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]));

        $response->assertOk();
        $response->assertSee('CATAT ABSEN');
    }

    public function test_satpam_scan_stores_latitude_and_longitude(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        $response = $this->actingAs($users['satpam'])->post(route('attendance.store', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]), [
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);

        $response->assertRedirect(route('attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]));

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $users['satpam']->id,
            'point_type' => 'CHECKIN',
            'status' => 'accepted',
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);
    }

    public function test_duplicate_scan_is_not_stored_in_database(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        $this->actingAs($users['satpam'])->post(route('attendance.store', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]), [
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);

        $this->actingAs($users['satpam'])->post(route('attendance.store', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]), [
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);

        // Only one accepted record should exist; the duplicate scan must NOT be inserted
        $this->assertDatabaseCount('attendance_logs', 1);
        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $users['satpam']->id,
            'point_type' => 'CHECKIN',
            'status' => 'accepted',
        ]);
        $this->assertDatabaseMissing('attendance_logs', [
            'status' => 'duplicate',
        ]);
    }

    public function test_admin_cannot_access_satpam_scan_route(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        $response = $this->actingAs($users['admin'])->get(route('attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]));

        $response->assertForbidden();
    }

    public function test_scan_without_gps_is_allowed_when_setting_is_disabled(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        AppSetting::current()->update([
            'require_gps' => false,
            'show_map' => true,
            'late_tolerance_minutes' => 0,
        ]);

        $response = $this->actingAs($users['satpam'])->post(route('attendance.store', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]), []);

        $response->assertRedirect(route('attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]));

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $users['satpam']->id,
            'status' => 'accepted',
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function test_scan_page_hides_map_when_setting_is_disabled(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        AppSetting::current()->update([
            'show_map' => false,
            'require_gps' => true,
            'late_tolerance_minutes' => 0,
        ]);

        $response = $this->actingAs($users['satpam'])->get(route('attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]));

        $response->assertOk();
        $response->assertDontSee('MAP');
    }

    public function test_scan_page_marks_late_based_on_tolerance_setting(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        AppSetting::current()->update([
            'late_tolerance_minutes' => 0,
            'require_gps' => true,
            'show_map' => true,
        ]);

        Schedule::query()->where('user_id', $users['satpam']->id)->update([
            'checkin_time' => now()->subHours(2)->format('H:i'),
        ]);

        $this->actingAs($users['satpam'])->post(route('attendance.store', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]), [
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);

        $response = $this->actingAs($users['satpam'])->get(route('attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CHECKIN',
        ]));

        $response->assertOk();
        $response->assertSee('Terlambat');
    }
}
