<?php

namespace Tests\Feature;

use App\Models\AttendanceCycle;
use App\Models\AttendanceLog;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_attendance_report_page(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamWithSchedule();

        $firstMonth = now()->subMonthsNoOverflow(2)->startOfMonth();

        $this->seedAttendance($satpam, $firstMonth, 'CHECKIN', 'REPORT-001');

        $response = $this->actingAs($admin)->get(route('attendance-reports.index', [
            'satpam_id' => $satpam->id,
            'month' => $firstMonth->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertSee('CETAK REKAP');
        $response->assertSee($satpam->name);
        $response->assertSee($firstMonth->translatedFormat('F Y'));
        $response->assertSee('Statistik Bulanan');
        $response->assertSee('Rekap Harian');
    }

    public function test_admin_can_download_attendance_report_pdf(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamWithSchedule();

        $month = now()->subMonthsNoOverflow(1)->startOfMonth();

        $this->seedAttendance($satpam, $month, 'CHECKIN', 'REPORT-002');
        $this->seedAttendance($satpam, $month, 'PATROL_1', 'REPORT-003', 30);

        $response = $this->actingAs($admin)->get(route('attendance-reports.download', [
            'satpam_id' => $satpam->id,
            'month' => $month->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="rekap-'.$satpam->id.'-'.$month->format('Y-m').'.pdf"');
    }

    public function test_report_month_list_is_not_hardcoded(): void
    {
        $admin = $this->createAdminUser();
        $satpam = $this->createSatpamWithSchedule();

        $oldMonth = now()->subMonthsNoOverflow(4)->startOfMonth();
        $this->seedAttendance($satpam, $oldMonth, 'CHECKIN', 'REPORT-OLD');

        $response = $this->actingAs($admin)->get(route('attendance-reports.index', [
            'satpam_id' => $satpam->id,
            'month' => $oldMonth->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertSee($oldMonth->translatedFormat('F Y'));
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        return $admin;
    }

    private function createSatpamWithSchedule(): User
    {
        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

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

        return $satpam;
    }

    private function seedAttendance(User $satpam, Carbon $month, string $pointType, string $token, int $minuteOffset = 0): void
    {
        $scannedAt = $month->copy()->startOfMonth()->addDays($minuteOffset === 30 ? 1 : 0)->setTime($minuteOffset === 30 ? 9 : 8, $minuteOffset);
        $cycle = AttendanceCycle::query()->create([
            'user_id' => $satpam->id,
            'cycle_date' => $scannedAt->toDateString(),
            'started_at' => $scannedAt->copy()->setTime(8, 0),
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
            'user_id' => $satpam->id,
            'qr_set_id' => $qrSet->id,
            'qr_set_point_id' => $point->id,
            'window_group' => match ($pointType) {
                'CHECKIN' => 'CHECKIN',
                'CHECKOUT' => 'CHECKOUT',
                default => $pointType,
            },
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
