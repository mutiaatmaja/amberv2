<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaScanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_satpam_can_access_pwa_scan_page(): void
    {
        $satpamRole = Role::query()->create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
        ]);

        /** @var User $satpam */
        $satpam = User::factory()->create();
        $satpam->addRole($satpamRole);

        $response = $this->actingAs($satpam)->get(route('scan-qr'));

        $response->assertOk();
        $response->assertSee('Scan QR');
        $response->assertSee('Mulai Scanner');
        $response->assertSee('Pilih Kamera');
        $response->assertSee('/js/html5-qrcode.min.js', false);
    }

    public function test_admin_cannot_access_pwa_scan_page(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->addRole($adminRole);

        $this->actingAs($admin)
            ->get(route('scan-qr'))
            ->assertForbidden();
    }
}
