<?php

namespace Tests\Feature;

use App\Models\CleaningSchedule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_cleaning_schedule_index(): void
    {
        [$admin] = $this->createAdminAndCleaner();

        $response = $this->actingAs($admin)->get(route('cleaning-schedules.index'));

        $response->assertOk();
        $response->assertSee('Daftar Jadwal Kebersihan');
    }

    public function test_satpam_cannot_access_cleaning_schedule_index(): void
    {
        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

        /** @var User $satpam */
        $satpam = User::factory()->create();
        $satpam->addRole($satpamRole);

        $response = $this->actingAs($satpam)->get(route('cleaning-schedules.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_store_schedule_for_cleaning_staff(): void
    {
        [$admin, $cleaningUser] = $this->createAdminAndCleaner();

        $response = $this->actingAs($admin)->post(route('cleaning-schedules.store'), [
            'user_id' => $cleaningUser->id,
            'checkin_time' => '07:00',
            'break_in_time' => '12:00',
            'break_out_time' => '13:00',
            'checkout_time' => '16:00',
        ]);

        $response->assertRedirect(route('cleaning-schedules.index'));
        $response->assertSessionHas('toast.message', 'Jadwal kebersihan berhasil ditambahkan.');

        $this->assertDatabaseHas('cleaning_schedules', [
            'user_id' => $cleaningUser->id,
            'checkin_time' => '07:00',
            'break_in_time' => '12:00',
            'break_out_time' => '13:00',
            'checkout_time' => '16:00',
        ]);
    }

    public function test_admin_can_update_cleaning_schedule(): void
    {
        [$admin, $cleaningUser] = $this->createAdminAndCleaner();

        $schedule = CleaningSchedule::query()->create([
            'user_id' => $cleaningUser->id,
            'checkin_time' => '07:00',
            'break_in_time' => '12:00',
            'break_out_time' => '13:00',
            'checkout_time' => '16:00',
        ]);

        $response = $this->actingAs($admin)->put(route('cleaning-schedules.update', $schedule), [
            'user_id' => $cleaningUser->id,
            'checkin_time' => '07:30',
            'break_in_time' => '12:30',
            'break_out_time' => '13:30',
            'checkout_time' => '16:30',
        ]);

        $response->assertRedirect(route('cleaning-schedules.index'));
        $response->assertSessionHas('toast.message', 'Jadwal kebersihan berhasil diperbarui.');

        $this->assertDatabaseHas('cleaning_schedules', [
            'id' => $schedule->id,
            'checkin_time' => '07:30',
            'break_in_time' => '12:30',
            'break_out_time' => '13:30',
            'checkout_time' => '16:30',
        ]);
    }

    public function test_admin_can_store_overnight_cleaning_schedule(): void
    {
        [$admin, $cleaningUser] = $this->createAdminAndCleaner();

        $response = $this->actingAs($admin)->post(route('cleaning-schedules.store'), [
            'user_id' => $cleaningUser->id,
            'checkin_time' => '20:00',
            'break_in_time' => '00:30',
            'break_out_time' => '01:00',
            'checkout_time' => '05:00',
        ]);

        $response->assertRedirect(route('cleaning-schedules.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cleaning_schedules', [
            'user_id' => $cleaningUser->id,
            'checkin_time' => '20:00',
            'break_in_time' => '00:30',
            'break_out_time' => '01:00',
            'checkout_time' => '05:00',
        ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function createAdminAndCleaner(): array
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $cleaningRole = Role::query()->create([
            'name' => 'kebersihan',
            'display_name' => 'Kebersihan',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        /** @var User $cleaningUser */
        $cleaningUser = User::factory()->create();
        $cleaningUser->addRole($cleaningRole);

        return [$admin, $cleaningUser];
    }
}
