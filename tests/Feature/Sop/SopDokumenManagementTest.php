<?php

namespace Tests\Feature\Sop;

use App\Models\SopDokumen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SopDokumenManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_kalab_can_view_sop_index_and_preview(): void
    {
        $kalab = User::factory()->create(['role' => 'Kalab']);
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        $dokumen = SopDokumen::factory()->create([
            'judul' => 'SOP APAR',
            'uploaded_by' => $petugas->id,
            'file_path' => 'sop-dokumen/1/demo.pdf',
            'original_filename' => 'demo.pdf',
        ]);

        Storage::disk('local')->put($dokumen->file_path, '%PDF-1.4 demo');

        $this->actingAs($kalab)
            ->get(route('sop'))
            ->assertOk()
            ->assertSee('Pedoman SOP')
            ->assertSee('SOP APAR')
            ->assertDontSee('Tambah Dokumen');

        $this->actingAs($kalab)
            ->get(route('sop.preview', $dokumen))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_satpam_can_preview_sop(): void
    {
        $satpam = User::factory()->create(['role' => 'Satpam']);
        $dokumen = SopDokumen::factory()->create([
            'file_path' => 'sop-dokumen/2/demo.pdf',
            'original_filename' => 'demo.pdf',
        ]);

        Storage::disk('local')->put($dokumen->file_path, '%PDF-1.4 demo');

        $this->actingAs($satpam)
            ->get(route('sop.preview', $dokumen))
            ->assertOk();
    }

    public function test_petugas_can_upload_update_and_delete_sop_pdf(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($petugas)
            ->post(route('sop.store'), [
                'judul' => 'SOP Evakuasi',
                'deskripsi' => 'Pedoman evakuasi darurat.',
                'file' => UploadedFile::fake()->create('evakuasi.pdf', 100, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('item.judul', 'SOP Evakuasi');

        $dokumen = SopDokumen::query()->firstOrFail();
        Storage::disk('local')->assertExists($dokumen->file_path);

        $this->actingAs($petugas)
            ->put(route('sop.update', $dokumen), [
                'judul' => 'SOP Evakuasi Diperbarui',
                'deskripsi' => 'Versi terbaru.',
            ])
            ->assertOk()
            ->assertJsonPath('item.judul', 'SOP Evakuasi Diperbarui');

        $this->actingAs($petugas)
            ->delete(route('sop.destroy', $dokumen))
            ->assertOk();

        $this->assertDatabaseMissing('sop_dokumen', ['id' => $dokumen->id]);
    }

    public function test_kalab_cannot_upload_sop(): void
    {
        $kalab = User::factory()->create(['role' => 'Kalab']);

        $this->actingAs($kalab)
            ->post(route('sop.store'), [
                'judul' => 'SOP B3',
                'file' => UploadedFile::fake()->create('b3.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('dashboard'));
    }
}
