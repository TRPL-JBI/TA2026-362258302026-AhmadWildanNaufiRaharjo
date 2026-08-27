<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_k3lh_can_access_inventaris_lokasi(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->get(route('inventaris.lokasi'))
            ->assertOk();
    }

    public function test_petugas_k3lh_can_access_manajemen_user(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->get(route('inventaris.user'))
            ->assertOk();
    }

    public function test_satpam_cannot_access_inventaris_lokasi(): void
    {
        $user = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($user)
            ->get(route('inventaris.lokasi'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_kalab_can_access_checklist_temuan_only_in_inventaris(): void
    {
        $user = User::factory()->create(['role' => 'Kalab']);

        $this->actingAs($user)
            ->get(route('inventaris.checklist-temuan'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('inventaris.lokasi'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_pimpinan_can_access_laporan_only(): void
    {
        $user = User::factory()->create(['role' => 'Pimpinan']);

        $this->actingAs($user)
            ->get(route('laporan'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('patroli.riwayat'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_notification_bell_only_visible_for_petugas_k3lh(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $satpam = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($petugas)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Notifikasi"', false);

        $this->actingAs($satpam)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('aria-label="Notifikasi"', false);
    }
}
