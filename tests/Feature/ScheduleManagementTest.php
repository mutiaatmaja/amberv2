<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_schedule_index(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        $response = $this->actingAs($admin)->get(route('schedules.index'));

        $response->assertOk();
        $response->assertSee('Daftar Jadwal Satpam');
    }

    public function test_satpam_cannot_access_schedule_index(): void
    {
        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

        /** @var User $satpam */
        $satpam = User::factory()->create();
        $satpam->addRole($satpamRole);

        $response = $this->actingAs($satpam)->get(route('schedules.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_store_schedule_for_satpam(): void
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

        $response = $this->actingAs($admin)->post(route('schedules.store'), [
            'user_id' => $satpam->id,
            'checkin_time' => '08:00',
            'checkout_time' => '17:00',
            'patrol_a_time' => '09:00',
            'patrol_b_time' => '12:00',
            'patrol_c_time' => '15:00',
        ]);

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('toast.message', 'Jadwal satpam berhasil ditambahkan.');

        $this->assertDatabaseHas('schedules', [
            'user_id' => $satpam->id,
            'checkin_time' => '08:00',
            'checkout_time' => '17:00',
            'patrol_a_time' => '09:00',
            'patrol_b_time' => '12:00',
            'patrol_c_time' => '15:00',
        ]);
    }

    public function test_admin_can_update_schedule(): void
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

        $schedule = Schedule::query()->create([
            'user_id' => $satpam->id,
            'checkin_time' => '08:00',
            'checkout_time' => '17:00',
            'patrol_a_time' => '09:00',
            'patrol_b_time' => '12:00',
            'patrol_c_time' => '15:00',
        ]);

        $response = $this->actingAs($admin)->put(route('schedules.update', $schedule), [
            'user_id' => $satpam->id,
            'checkin_time' => '08:30',
            'checkout_time' => '17:30',
            'patrol_a_time' => '09:30',
            'patrol_b_time' => '12:30',
            'patrol_c_time' => '15:30',
        ]);

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('toast.message', 'Jadwal satpam berhasil diperbarui.');

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'checkin_time' => '08:30',
            'checkout_time' => '17:30',
            'patrol_a_time' => '09:30',
            'patrol_b_time' => '12:30',
            'patrol_c_time' => '15:30',
        ]);
    }
}
