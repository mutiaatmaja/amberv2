<?php

namespace Tests\Feature;

use App\Models\CleaningSchedule;
use App\Models\QrSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningProcedureValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaning_end_to_end_procedure_is_recorded_and_visible_to_admin(): void
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

        /** @var User $cleaningUser */
        $cleaningUser = User::factory()->create([
            'name' => 'Kebersihan Shift Pagi',
        ]);
        $cleaningUser->addRole($cleaningRole);

        CleaningSchedule::query()->create([
            'user_id' => $cleaningUser->id,
            'checkin_time' => '08:00',
            'break_in_time' => '12:00',
            'break_out_time' => '13:00',
            'checkout_time' => '17:00',
        ]);

        $qrSet = QrSet::query()->create([
            'code' => 'QR-CLEANING-PROCEDURE',
            'token_prefix' => 'CLEANINGPROCEDURETOKN',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $point = $qrSet->points()->create([
            'point_type' => 'CLEANING_CHECKIN',
            'token' => 'CLEANINGPROCEDURECHECKINTOKEN1234567890ABCDEFGH',
        ]);

        // Kebersihan login lalu membuka halaman scanner (UI memuat tombol install aplikasi PWA).
        $this->actingAs($cleaningUser)
            ->get(route('cleaning-scan-qr'))
            ->assertOk()
            ->assertSee('Scan QR Kebersihan')
            ->assertSee('Install Aplikasi');

        // Kebersihan scan QR, absensi harus tercatat sebagai accepted.
        $this->actingAs($cleaningUser)
            ->post(route('cleaning-attendance.store', [
                'token' => $point->token,
                'pointType' => 'CLEANING_CHECKIN',
            ]), [
                'latitude' => -6.2012,
                'longitude' => 106.8163,
            ])
            ->assertRedirect(route('cleaning-attendance.scan', [
                'token' => $point->token,
                'pointType' => 'CLEANING_CHECKIN',
            ]));

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $cleaningUser->id,
            'point_type' => 'CLEANING_CHECKIN',
            'status' => 'accepted',
        ]);

        // Admin dapat melihat catatan absensi kebersihan di halaman kelola absensi kebersihan.
        $this->actingAs($admin)
            ->get(route('cleaning-attendance-logs.index', [
                'user_id' => $cleaningUser->id,
                'month' => now()->format('Y-m'),
            ]))
            ->assertOk()
            ->assertSee('Kelola Absensi Kebersihan')
            ->assertSee($cleaningUser->name)
            ->assertSee('Checkin');
    }
}
