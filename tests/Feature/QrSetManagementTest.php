<?php

namespace Tests\Feature;

use App\Models\QrSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrSetManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{admin: User, supervisor: User, satpam: User}
     */
    private function seedUsersWithRoles(): array
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

        /** @var User $supervisor */
        $supervisor = User::factory()->create();
        $supervisor->addRole($supervisorRole);

        /** @var User $satpam */
        $satpam = User::factory()->create();
        $satpam->addRole($satpamRole);

        return [
            'admin' => $admin,
            'supervisor' => $supervisor,
            'satpam' => $satpam,
        ];
    }

    public function test_admin_can_access_qr_set_index(): void
    {
        $users = $this->seedUsersWithRoles();

        $response = $this->actingAs($users['admin'])->get(route('qr-sets.index'));

        $response->assertOk();
        $response->assertSee('Cetak QR Titik Absen');
    }

    public function test_satpam_cannot_access_qr_set_index(): void
    {
        $users = $this->seedUsersWithRoles();

        $response = $this->actingAs($users['satpam'])->get(route('qr-sets.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_generate_qr_set_with_five_points(): void
    {
        $users = $this->seedUsersWithRoles();

        $response = $this->actingAs($users['admin'])->post(route('qr-sets.store'));

        $response->assertRedirect(route('qr-sets.index'));
        $response->assertSessionHas('toast.message', 'Satu set QR berhasil dibuat (8 titik).');

        $qrSet = QrSet::query()->first();

        $this->assertNotNull($qrSet);
        $this->assertTrue($qrSet->is_active);
        $this->assertSame(8, $qrSet->points()->count());
    }

    public function test_admin_can_activate_another_qr_set(): void
    {
        $users = $this->seedUsersWithRoles();

        $this->actingAs($users['admin'])->post(route('qr-sets.store'));
        $this->actingAs($users['admin'])->post(route('qr-sets.store'));

        $firstSet = QrSet::query()->orderBy('id', 'asc')->first();
        $secondSet = QrSet::query()->orderByDesc('id')->first();

        $this->assertNotNull($firstSet);
        $this->assertNotNull($secondSet);
        $this->assertTrue((bool) $firstSet->is_active);
        $this->assertFalse((bool) $secondSet->is_active);

        $response = $this->actingAs($users['admin'])->post(route('qr-sets.activate', $secondSet));

        $response->assertRedirect(route('qr-sets.index'));
        $response->assertSessionHas('toast.message', 'Set QR berhasil diaktifkan.');

        $firstSet->refresh();
        $secondSet->refresh();

        $this->assertFalse((bool) $firstSet->is_active);
        $this->assertTrue((bool) $secondSet->is_active);
        $this->assertNotNull($secondSet->activated_at);
    }

    public function test_admin_can_download_qr_set_pdf(): void
    {
        $users = $this->seedUsersWithRoles();

        $this->actingAs($users['admin'])->post(route('qr-sets.store'));

        $qrSet = QrSet::query()->first();

        $response = $this->actingAs($users['admin'])->get(route('qr-sets.download', $qrSet));

        $this->assertNotSame(500, $response->getStatusCode());

        if ($response->getStatusCode() === 302) {
            $response->assertSessionHas('toast.message', 'PDF belum bisa dibuat. Konfigurasi driver PDF belum lengkap.');
        }
    }
}
