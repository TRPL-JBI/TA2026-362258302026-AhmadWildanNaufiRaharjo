<?php

namespace Tests\Feature\Inventaris;

use App\Models\ItemChecklist;
use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTemuanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_create_checklist_for_gedung_not_laboratorium(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $gedung = Lokasi::factory()->create([
            'nama_lokasi' => 'Gedung A',
            'jenis_lokasi' => 'Gedung',
        ]);

        $this->actingAs($petugas)->post(route('inventaris.checklist-temuan.store'), [
            'nama_checklist' => 'Checklist Gedung A',
            'lokasi_id' => $gedung->id,
        ])->assertRedirect(route('inventaris.checklist-temuan'));

        $this->assertDatabaseHas('master_checklist', [
            'nama_checklist' => 'Checklist Gedung A',
            'lokasi_id' => $gedung->id,
            'jenis_pengelola' => 'Petugas K3LH',
        ]);
    }

    public function test_petugas_cannot_create_checklist_for_laboratorium(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lab = Lokasi::factory()->create([
            'nama_lokasi' => 'Lab Kimia',
            'jenis_lokasi' => 'Laboratorium',
        ]);

        $this->actingAs($petugas)->post(route('inventaris.checklist-temuan.store'), [
            'nama_checklist' => 'Checklist Lab',
            'lokasi_id' => $lab->id,
        ])->assertStatus(422);

        $this->assertDatabaseCount('master_checklist', 0);
    }

    public function test_kalab_can_create_checklist_for_own_laboratorium(): void
    {
        $lab = Lokasi::factory()->create([
            'nama_lokasi' => 'Lab Kimia',
            'jenis_lokasi' => 'Laboratorium',
        ]);
        $kalab = User::factory()->create([
            'role' => 'Kalab',
            'lokasi_id' => $lab->id,
        ]);

        $this->actingAs($kalab)->post(route('inventaris.checklist-temuan.store'), [
            'nama_checklist' => 'Checklist Lab Kimia',
            'lokasi_id' => $lab->id,
        ])->assertRedirect(route('inventaris.checklist-temuan'));

        $this->assertDatabaseHas('master_checklist', [
            'nama_checklist' => 'Checklist Lab Kimia',
            'jenis_pengelola' => 'Kalab',
            'dibuat_oleh_id' => $kalab->id,
        ]);
    }

    public function test_petugas_only_sees_non_lab_checklists_on_index(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $gedung = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung', 'nama_lokasi' => 'Gedung A']);
        $lab = Lokasi::factory()->create(['jenis_lokasi' => 'Laboratorium', 'nama_lokasi' => 'Lab X']);

        MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Gedung',
            'lokasi_id' => $gedung->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Lab',
            'lokasi_id' => $lab->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Kalab',
            'status' => 'Aktif',
        ]);

        $this->actingAs($petugas)
            ->get(route('inventaris.checklist-temuan'))
            ->assertOk()
            ->assertSee('Checklist Gedung')
            ->assertDontSee('Checklist Lab');
    }

    public function test_petugas_can_add_item_to_checklist(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $gedung = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung']);
        $master = MasterChecklist::query()->create([
            'nama_checklist' => 'CL Gedung',
            'lokasi_id' => $gedung->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $this->actingAs($petugas)->post(route('inventaris.checklist-temuan.items.store', $master), [
            'nama_item' => 'Kabel terkelupas',
            'deskripsi' => 'Periksa instalasi',
            'probability' => 3,
            'severity' => 4,
        ])->assertRedirect(route('inventaris.checklist-temuan'));

        $item = ItemChecklist::query()->first();
        $this->assertNotNull($item);
        $this->assertSame(12, $item->skor_risiko);
        $this->assertSame('Tinggi', $item->level_risiko);
    }
}
