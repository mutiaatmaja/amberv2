<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\CleaningSchedule;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningAttendanceScanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{admin: User, cleaning: User}
     */
    private function seedUsersWithRoles(): array
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin'],
        );

        $cleaningRole = Role::query()->firstOrCreate(
            ['name' => 'kebersihan'],
            ['display_name' => 'Kebersihan'],
        );

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        /** @var User $cleaning */
        $cleaning = User::factory()->create();
        $cleaning->addRole($cleaningRole);

        CleaningSchedule::query()->create([
            'user_id' => $cleaning->id,
            'checkin_time' => '08:00',
            'break_in_time' => '12:00',
            'break_out_time' => '13:00',
            'checkout_time' => '17:00',
        ]);

        return [
            'admin' => $admin,
            'cleaning' => $cleaning,
        ];
    }

    private function createActiveCheckinPoint(): array
    {
        $qrSet = QrSet::query()->create([
            'code' => 'QR-CLEANING-001',
            'token_prefix' => 'TOKENPREFIXCLEANING001',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $point = $qrSet->points()->create([
            'point_type' => 'CLEANING_CHECKIN',
            'token' => 'CLEANINGCHECKINTOKEN1234567890ABCDEF1234567890ABCD',
        ]);

        return [
            'set' => $qrSet,
            'point' => $point,
        ];
    }

    private function createActiveBreakInPoint(): array
    {
        $qrSet = QrSet::query()->create([
            'code' => 'QR-CLEANING-BREAK-IN',
            'token_prefix' => 'TOKENPREFIXCLEANINGBREAKIN',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $point = $qrSet->points()->create([
            'point_type' => 'CLEANING_BREAK_IN',
            'token' => 'CLEANINGBREAKINTOKEN1234567890ABCDEF1234567890ABCD',
        ]);

        return [
            'set' => $qrSet,
            'point' => $point,
        ];
    }

    public function test_cleaning_staff_can_open_scan_page(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        $response = $this->actingAs($users['cleaning'])->get(route('cleaning-attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CLEANING_CHECKIN',
        ]));

        $response->assertOk();
        $response->assertSee('CATAT ABSEN');
        $response->assertSee('Absensi Kebersihan');
    }

    public function test_cleaning_scan_stores_latitude_and_longitude(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        AppSetting::current()->update([
            'require_gps' => true,
            'show_map' => true,
        ]);

        $response = $this->actingAs($users['cleaning'])->post(route('cleaning-attendance.store', [
            'token' => $point['point']->token,
            'pointType' => 'CLEANING_CHECKIN',
        ]), [
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);

        $response->assertRedirect(route('cleaning-attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CLEANING_CHECKIN',
        ]));

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $users['cleaning']->id,
            'point_type' => 'CLEANING_CHECKIN',
            'status' => 'accepted',
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);
    }

    public function test_cleaning_break_in_is_accepted_while_still_in_the_same_session(): void
    {
        $users = $this->seedUsersWithRoles();
        $checkinPoint = $this->createActiveCheckinPoint();
        $breakInPoint = $this->createActiveBreakInPoint();

        $this->actingAs($users['cleaning'])->post(route('cleaning-attendance.store', [
            'token' => $checkinPoint['point']->token,
            'pointType' => 'CLEANING_CHECKIN',
        ]), [
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);

        $this->travelTo('2026-09-01 12:10:00');

        $response = $this->actingAs($users['cleaning'])->post(route('cleaning-attendance.store', [
            'token' => $breakInPoint['point']->token,
            'pointType' => 'CLEANING_BREAK_IN',
        ]), [
            'latitude' => -6.2012001,
            'longitude' => 106.8163012,
        ]);

        $response->assertRedirect(route('cleaning-attendance.scan', [
            'token' => $breakInPoint['point']->token,
            'pointType' => 'CLEANING_BREAK_IN',
        ]));
        $response->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $users['cleaning']->id,
            'point_type' => 'CLEANING_BREAK_IN',
            'status' => 'accepted',
        ]);
    }

    public function test_admin_cannot_access_cleaning_scan_route(): void
    {
        $users = $this->seedUsersWithRoles();
        $point = $this->createActiveCheckinPoint();

        $response = $this->actingAs($users['admin'])->get(route('cleaning-attendance.scan', [
            'token' => $point['point']->token,
            'pointType' => 'CLEANING_CHECKIN',
        ]));

        $response->assertForbidden();
    }
}
