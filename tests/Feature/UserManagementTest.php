<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_user_management_index(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Daftar Pengguna');
    }

    public function test_satpam_cannot_access_user_management_index(): void
    {
        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

        /** @var User $satpam */
        $satpam = User::factory()->create();
        $satpam->addRole($satpamRole);

        $response = $this->actingAs($satpam)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_store_user_with_role(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        Role::query()->create([
            'name' => 'supervisor',
            'display_name' => 'Supervisor',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Operator Baru',
            'email' => 'operator@example.com',
            'password' => 'password123',
            'role' => 'supervisor',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('toast.message', 'Pengguna berhasil ditambahkan.');

        $user = User::query()->where('email', 'operator@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('supervisor'));
    }

    public function test_admin_can_update_user_and_change_role(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $supervisorRole = Role::query()->create([
            'name' => 'supervisor',
            'display_name' => 'Supervisor',
        ]);

        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        /** @var User $target */
        $target = User::factory()->create([
            'name' => 'User Lama',
            'email' => 'old@example.com',
        ]);
        $target->addRole($satpamRole);

        $response = $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => 'User Baru',
            'email' => 'new@example.com',
            'password' => '',
            'role' => 'supervisor',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('toast.message', 'Data pengguna berhasil diperbarui.');

        $target->refresh();

        $this->assertSame('User Baru', $target->name);
        $this->assertSame('new@example.com', $target->email);
        $this->assertTrue($target->hasRole('supervisor'));
        $this->assertFalse($target->hasRole('satpam'));
    }
}
