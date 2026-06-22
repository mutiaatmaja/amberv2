<?php

namespace Tests\Feature;

use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\CleaningSchedule;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningAttendanceLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_cleaning_attendance_log_index(): void
    {
        $admin = $this->createAdminUser();
        $cleaningUser = $this->createCleaningUser();
        $this->createLog($cleaningUser);

        $response = $this->actingAs($admin)->get(route('cleaning-attendance-logs.index'));

        $response->assertOk();
        $response->assertSee('Kelola Absensi Kebersihan');
        $response->assertSee($cleaningUser->name);
    }

    public function test_admin_can_update_cleaning_attendance_log(): void
    {
        $admin = $this->createAdminUser();
        $cleaningUser = $this->createCleaningUser();
        $log = $this->createLog($cleaningUser);

        $response = $this->actingAs($admin)
            ->put(route('cleaning-attendance-logs.update', $log), [
                'scanned_at' => '2026-05-01 09:30:00',
                'status' => 'rejected',
                'reason' => 'Absensi dikoreksi oleh admin',
            ]);

        $response->assertRedirect(route('cleaning-attendance-logs.index', [
            'user_id' => $cleaningUser->id,
            'month' => '2026-05',
        ]));

        $log->refresh();
        $this->assertEquals('rejected', $log->status);
        $this->assertEquals('Absensi dikoreksi oleh admin', $log->reason);
    }

    private function createAdminUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $user = User::factory()->create();
        $user->addRole($role);

        return $user;
    }

    private function createCleaningUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'kebersihan'], ['display_name' => 'Kebersihan']);
        $user = User::factory()->create(['name' => 'Petugas Kebersihan Satu']);
        $user->addRole($role);

        CleaningSchedule::query()->create([
            'user_id' => $user->id,
            'checkin_time' => '08:00',
            'break_in_time' => '12:00',
            'break_out_time' => '13:00',
            'checkout_time' => '17:00',
        ]);

        return $user;
    }

    private function createLog(User $cleaningUser): AttendanceLog
    {
        $cycle = AttendanceCycle::query()->create([
            'user_id' => $cleaningUser->id,
            'cycle_date' => now()->toDateString(),
            'started_at' => now()->copy()->startOfDay()->addHours(8),
            'expected_end_at' => now()->copy()->startOfDay()->addHours(17),
            'ended_at' => now()->copy()->startOfDay()->addHours(17),
            'status' => AttendanceCycle::STATUS_CLOSED,
            'checkout_mode' => AttendanceCycle::CHECKOUT_MODE_MANUAL,
        ]);

        $qrSet = QrSet::query()->create([
            'code' => 'QR-CLEAN-TEST-'.uniqid(),
            'token_prefix' => strtoupper(substr(uniqid('CLEAN', true), 0, 18)),
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $point = $qrSet->points()->create([
            'point_type' => 'CLEANING_CHECKIN',
            'token' => 'CLEAN-TOKEN-'.uniqid(),
        ]);

        return AttendanceLog::query()->create([
            'attendance_cycle_id' => $cycle->id,
            'user_id' => $cleaningUser->id,
            'qr_set_id' => $qrSet->id,
            'qr_set_point_id' => $point->id,
            'window_group' => 'CLEANING_CHECKIN',
            'point_type' => 'CLEANING_CHECKIN',
            'token' => $point->token,
            'scanned_at' => now(),
            'status' => 'accepted',
            'reason' => null,
            'latitude' => -6.2012,
            'longitude' => 106.8163,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
        ]);
    }
}
