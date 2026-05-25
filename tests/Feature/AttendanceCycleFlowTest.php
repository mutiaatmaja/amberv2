<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\AttendanceCycle;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCycleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_checkin_opens_a_new_attendance_cycle(): void
    {
        $satpam = $this->createSatpamWithSchedule([
            'checkin_time' => '18:00',
            'checkout_time' => '04:00',
        ]);
        $point = $this->createPoint('CHECKIN');

        Carbon::setTestNow('2026-05-25 18:05:00');

        $this->actingAs($satpam)->post(route('attendance.store', [
            'token' => $point->token,
            'pointType' => 'CHECKIN',
        ]), [
            'latitude' => -6.2,
            'longitude' => 106.8,
        ])->assertRedirect();

        $cycle = AttendanceCycle::query()->firstOrFail();

        $this->assertEquals($satpam->id, $cycle->user_id);
        $this->assertEquals('2026-05-25', $cycle->cycle_date->format('Y-m-d'));
        $this->assertEquals(AttendanceCycle::STATUS_OPEN, $cycle->status);
        $this->assertEquals('2026-05-26 04:00:00', $cycle->expected_end_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_cycle_id' => $cycle->id,
            'point_type' => 'CHECKIN',
            'window_group' => 'CHECKIN',
            'status' => 'accepted',
        ]);
    }

    public function test_patrol_requires_an_active_cycle(): void
    {
        $satpam = $this->createSatpamWithSchedule();
        $point = $this->createPoint('PATROL_1');

        Carbon::setTestNow('2026-05-25 09:10:00');

        $response = $this->actingAs($satpam)->post(route('attendance.store', [
            'token' => $point->token,
            'pointType' => 'PATROL_1',
        ]), [
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('toast.type', 'error');
        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $satpam->id,
            'point_type' => 'PATROL_1',
            'status' => 'rejected',
            'reason' => 'no_active_cycle',
        ]);
    }

    public function test_previous_window_point_is_rejected_after_next_window_has_started(): void
    {
        $satpam = $this->createSatpamWithSchedule([
            'checkin_time' => '18:00',
            'checkout_time' => '04:00',
            'patrol_1_time' => '23:00',
            'standby_1_time' => '23:30',
            'patrol_2_time' => '01:00',
            'standby_2_time' => '01:30',
        ]);
        $checkin = $this->createPoint('CHECKIN');
        $standby = $this->createPoint('STANDBY_1');
        $patrol = $this->createPoint('PATROL_1');

        AppSetting::current()->update([
            'early_tolerance_minutes' => 30,
            'late_tolerance_minutes' => 0,
            'auto_checkout_grace_minutes' => 0,
            'require_gps' => true,
            'show_map' => true,
        ]);

        Carbon::setTestNow('2026-05-25 18:02:00');
        $this->actingAs($satpam)->post(route('attendance.store', [
            'token' => $checkin->token,
            'pointType' => 'CHECKIN',
        ]), [
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        Carbon::setTestNow('2026-05-26 00:20:00');
        $this->actingAs($satpam)->post(route('attendance.store', [
            'token' => $standby->token,
            'pointType' => 'STANDBY_1',
        ]), [
            'latitude' => -6.2,
            'longitude' => 106.8,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $satpam->id,
            'point_type' => 'STANDBY_1',
            'status' => 'accepted',
            'window_group' => 'STANDBY_1',
        ]);

        Carbon::setTestNow('2026-05-26 01:05:00');
        $response = $this->actingAs($satpam)->post(route('attendance.store', [
            'token' => $patrol->token,
            'pointType' => 'PATROL_1',
        ]), [
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('toast.type', 'error');
        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $satpam->id,
            'point_type' => 'PATROL_1',
            'status' => 'rejected',
            'reason' => 'outside_window',
        ]);
    }

    public function test_cycle_is_auto_closed_with_expired_checkout_log(): void
    {
        $satpam = $this->createSatpamWithSchedule([
            'checkin_time' => '18:00',
            'checkout_time' => '04:00',
        ]);
        $checkin = $this->createPoint('CHECKIN');

        Carbon::setTestNow('2026-05-25 18:05:00');
        $this->actingAs($satpam)->post(route('attendance.store', [
            'token' => $checkin->token,
            'pointType' => 'CHECKIN',
        ]), [
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        Carbon::setTestNow('2026-05-26 04:01:00');
        AttendanceCycle::expireOpenCycles($satpam->id);

        $cycle = AttendanceCycle::query()->firstOrFail();
        $cycle->refresh();

        $this->assertEquals(AttendanceCycle::STATUS_EXPIRED, $cycle->status);
        $this->assertEquals(AttendanceCycle::CHECKOUT_MODE_AUTO, $cycle->checkout_mode);
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_cycle_id' => $cycle->id,
            'point_type' => 'CHECKOUT',
            'status' => 'accepted',
            'reason' => 'expired',
        ]);
    }

    private function createSatpamWithSchedule(array $overrides = []): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'satpam'], ['display_name' => 'Satpam']);
        $satpam = User::factory()->create();
        $satpam->addRole($role);

        Schedule::query()->create(array_merge([
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
        ], $overrides));

        return $satpam;
    }

    private function createPoint(string $pointType): object
    {
        $qrSet = QrSet::query()->create([
            'code' => 'QR-'.str_replace('_', '-', $pointType).'-'.uniqid(),
            'token_prefix' => strtoupper(substr(str_pad($pointType, 18, 'X'), 0, 18)),
            'is_active' => true,
            'activated_at' => now(),
        ]);

        return $qrSet->points()->create([
            'point_type' => $pointType,
            'token' => strtoupper(str_replace('.', '', uniqid($pointType, true))),
        ]);
    }
}
