<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppSettingManagementTest extends TestCase
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

        return [
            'admin' => $admin,
            'satpam' => $satpam,
        ];
    }

    public function test_admin_can_open_settings_page(): void
    {
        $users = $this->seedUsersWithRoles();

        $response = $this->actingAs($users['admin'])->get(route('settings.edit'));

        $response->assertOk();
        $response->assertSee('Pengaturan Absensi');
    }

    public function test_satpam_cannot_open_settings_page(): void
    {
        $users = $this->seedUsersWithRoles();

        $response = $this->actingAs($users['satpam'])->get(route('settings.edit'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_settings(): void
    {
        $users = $this->seedUsersWithRoles();

        $response = $this->actingAs($users['admin'])->put(route('settings.update'), [
            'late_tolerance_minutes' => 15,
            'require_gps' => '0',
            'show_map' => '1',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $response->assertSessionHas('toast.message', 'Pengaturan berhasil diperbarui.');

        $settings = AppSetting::current();

        $this->assertSame(15, $settings->late_tolerance_minutes);
        $this->assertFalse($settings->require_gps);
        $this->assertTrue($settings->show_map);
    }
}
