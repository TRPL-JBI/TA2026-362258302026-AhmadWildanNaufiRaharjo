<?php

namespace Tests\Feature\Laporan;

use App\Models\Apar;
use App\Models\DetailInspeksi;
use App\Models\ItemChecklist;
use App\Models\LaporanGenerated;
use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\User;
use App\Support\PatroliPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class PatroliLaporanGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_mark_temuan_selesai_generates_laporan_docx(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Gedung',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $item = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'APAR siap',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
        ]);

        $this->actingAs($petugas)->postJson(route('patroli.inspeksi.store'), [
            'sections' => [[
                'lokasi_id' => $lokasi->id,
                'master_checklist_id' => $checklist->id,
                'items' => [['item_checklist_id' => $item->id, 'status' => 'ya']],
            ]],
        ])->assertOk();

        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.temuan.selesai', ['periode' => $periode]))
            ->assertOk()
            ->assertJsonPath('status', 'Selesai');

        $laporan = LaporanGenerated::query()->first();
        $this->assertNotNull($laporan);
        $this->assertSame(LaporanGenerated::JENIS_K3L, $laporan->jenis_laporan);
        $this->assertSame($petugas->id, $laporan->user_id);
        $this->assertNotNull($laporan->file_path_docx);
        Storage::disk('local')->assertExists($laporan->file_path_docx);

        $this->actingAs($petugas)
            ->get(route('laporan'))
            ->assertOk()
            ->assertSee('Laporan Patroli K3LH', false);

        $this->actingAs($petugas)
            ->get(route('laporan.download', $laporan))
            ->assertOk()
            ->assertHeader('content-disposition');

        $docxPath = Storage::disk('local')->path($laporan->fresh()->file_path_docx);
        $zip = new ZipArchive;
        $zip->open($docxPath);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('POLITEKNIK NEGERI BANYUWANGI', $xml);
        $this->assertStringContainsString('PENDAHULUAN', $xml);
        $this->assertStringContainsString('HASIL INSPEKSI TEMUAN BAHAYA', $xml);
    }

    public function test_mark_temuan_selesai_embeds_foto_in_laporan_docx(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Gedung',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $itemTidak = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Kabel terkelupas',
            'probability' => 4,
            'severity' => 4,
            'skor_risiko' => 16,
            'level_risiko' => 'Sangat Tinggi',
            'status' => 'Aktif',
        ]);

        $fotoTemuan = UploadedFile::fake()->image('temuan.jpg', 640, 480);

        $this->actingAs($petugas)->post(route('patroli.inspeksi.store'), [
            'sections' => [[
                'lokasi_id' => $lokasi->id,
                'master_checklist_id' => $checklist->id,
                'items' => [[
                    'item_checklist_id' => $itemTidak->id,
                    'status' => 'tidak',
                    'analisa_risiko' => 'Kabel tidak terlindung',
                    'rekomendasi' => 'Ganti kabel',
                ]],
            ]],
            'foto_item' => [
                $itemTidak->id => $fotoTemuan,
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $detailTidak = DetailInspeksi::query()->firstOrFail();
        Storage::disk('public')->assertExists($detailTidak->foto_path);

        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.temuan.selesai', ['periode' => $periode]))
            ->assertOk();

        $laporan = LaporanGenerated::query()->firstOrFail();
        $docxPath = Storage::disk('local')->path($laporan->file_path_docx);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true);

        $hasMedia = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with($zip->getNameIndex($i), 'word/media/')) {
                $hasMedia = true;
                break;
            }
        }

        $zip->close();

        $this->assertTrue($hasMedia, 'DOCX laporan temuan harus menyertakan foto.');
    }

    public function test_mark_apar_selesai_generates_laporan_xlsx(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $apar = Apar::factory()->create([
            'lokasi_id' => $lokasi->id,
            'keterangan' => null,
        ]);

        $fotoApar = UploadedFile::fake()->image('apar-kondisi.jpg', 720, 720);

        $this->actingAs($petugas)->postJson(route('patroli.apar.store'), [
            'pemeriksaan' => [[
                'apar_id' => $apar->id,
                'kondisi_tabung' => 'Baik',
                'kondisi_segel' => 'tersegel',
            ]],
            'foto_apar' => [
                $apar->id => [$fotoApar],
            ],
        ])->assertOk();

        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.apar.selesai', ['periode' => $periode]))
            ->assertOk();

        $laporan = LaporanGenerated::query()
            ->where('jenis_laporan', LaporanGenerated::JENIS_INVENTARIS_APAR)
            ->first();

        $this->assertNotNull($laporan);
        $this->assertNull($laporan->file_path_docx);
        $this->assertNotNull($laporan->file_path_xlsx);
        Storage::disk('local')->assertExists($laporan->file_path_xlsx);

        $xlsxPath = Storage::disk('local')->path($laporan->file_path_xlsx);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($xlsxPath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame(
            'INVENTARIS APAR DI POLITEKNIK NEGERI BANYUWANGI',
            $sheet->getCell('A1')->getCalculatedValue(),
        );
        $this->assertSame('LOKASI APAR', $sheet->getCell('B3')->getCalculatedValue());
        $this->assertSame($apar->kode_apar, $sheet->getCell('B5')->getCalculatedValue());
        $this->assertSame(1, $sheet->getCell('C5')->getCalculatedValue());
        $this->assertSame('Baik tersegel', $sheet->getCell('E5')->getCalculatedValue());
        $this->assertStringContainsString(
            'ex.'.$apar->tanggal_expired->format('Y'),
            (string) $sheet->getCell('F5')->getCalculatedValue(),
        );
    }

    public function test_destroy_temuan_riwayat_deletes_generated_laporan(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Gedung',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $item = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'APAR siap',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
        ]);

        $this->actingAs($petugas)->postJson(route('patroli.inspeksi.store'), [
            'sections' => [[
                'lokasi_id' => $lokasi->id,
                'master_checklist_id' => $checklist->id,
                'items' => [['item_checklist_id' => $item->id, 'status' => 'ya']],
            ]],
        ])->assertOk();

        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.temuan.selesai', ['periode' => $periode]))
            ->assertOk();

        $path = LaporanGenerated::query()->value('file_path_docx');
        $this->assertNotNull($path);

        $this->actingAs($petugas)
            ->deleteJson(route('patroli.riwayat.temuan.destroy', ['periode' => $periode]))
            ->assertOk();

        $this->assertSame(0, LaporanGenerated::query()->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_pimpinan_can_download_patroli_laporan(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $pimpinan = User::factory()->create(['role' => 'Pimpinan']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Gedung',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $item = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'APAR siap',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
        ]);

        $this->actingAs($petugas)->postJson(route('patroli.inspeksi.store'), [
            'sections' => [[
                'lokasi_id' => $lokasi->id,
                'master_checklist_id' => $checklist->id,
                'items' => [['item_checklist_id' => $item->id, 'status' => 'ya']],
            ]],
        ])->assertOk();

        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.temuan.selesai', ['periode' => $periode]))
            ->assertOk();

        $laporan = LaporanGenerated::query()->firstOrFail();

        $this->actingAs($pimpinan)
            ->get(route('laporan'))
            ->assertOk()
            ->assertSee('Laporan Patroli K3LH', false);

        $this->actingAs($pimpinan)
            ->get(route('laporan.download', $laporan))
            ->assertOk();
    }
}
