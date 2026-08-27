<?php

namespace Tests\Feature\Inventaris;

use App\Models\TitikIpam;
use App\Models\UnitIpam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IpamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_create_unit_and_titik(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)->post(route('inventaris.ipam.unit.store'), [
            '_form' => 'unit',
            'nama_unit' => 'IPAM Gedung A',
            'deskripsi' => 'Unit utama',
        ])->assertRedirect(route('inventaris.ipam'));

        $unit = UnitIpam::query()->first();
        $this->assertNotNull($unit);
        $this->assertSame('IPM-01', $unit->kode_unit);

        $this->actingAs($user)->post(route('inventaris.ipam.titik.store'), [
            '_form' => 'titik',
            'unit_ipam_id' => $unit->id,
            'titik_lokasi' => 'Kantin',
            'deskripsi' => 'Titik sampling kantin',
        ])->assertRedirect(route('inventaris.ipam'))
            ->assertSessionHas('success');

        $titik = TitikIpam::query()->first();
        $this->assertNotNull($titik);
        $this->assertSame('Kantin', $titik->titik_lokasi);
        $this->assertFalse(
            Schema::hasColumn('titik_ipam', 'qr_code_path'),
            'Kolom qr_code_path seharusnya sudah dihapus dari tabel titik_ipam.',
        );
    }

    public function test_petugas_can_view_ipam_index(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $unit = UnitIpam::factory()->create(['nama_unit' => 'IPAM 1']);
        TitikIpam::factory()->create([
            'unit_ipam_id' => $unit->id,
            'titik_lokasi' => 'Inlet',
        ]);

        $this->actingAs($user)
            ->get(route('inventaris.ipam'))
            ->assertOk()
            ->assertSee('IPAM 1')
            ->assertSee('Inlet');
    }

    public function test_unit_cannot_be_deleted_when_it_has_titik(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $unit = UnitIpam::factory()->create();
        TitikIpam::factory()->create(['unit_ipam_id' => $unit->id]);

        $this->actingAs($user)
            ->delete(route('inventaris.ipam.unit.destroy', $unit))
            ->assertRedirect(route('inventaris.ipam'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('unit_ipam', ['id' => $unit->id]);
    }

    public function test_titik_name_must_be_unique_within_unit(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $unit = UnitIpam::factory()->create();
        TitikIpam::factory()->create([
            'unit_ipam_id' => $unit->id,
            'titik_lokasi' => 'Outlet',
        ]);

        $this->actingAs($user)->post(route('inventaris.ipam.titik.store'), [
            '_form' => 'titik',
            'unit_ipam_id' => $unit->id,
            'titik_lokasi' => 'Outlet',
        ])->assertSessionHasErrors('titik_lokasi');
    }

    public function test_satpam_cannot_store_ipam_data(): void
    {
        $user = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($user)->post(route('inventaris.ipam.unit.store'), [
            'nama_unit' => 'Test',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('unit_ipam', 0);
    }
}
