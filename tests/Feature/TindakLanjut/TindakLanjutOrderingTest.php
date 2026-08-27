<?php

namespace Tests\Feature\TindakLanjut;

use App\Models\DetailInspeksi;
use App\Models\InspeksiK3l;
use App\Models\ItemChecklist;
use App\Models\LaporanGenerated;
use App\Models\LaporanInsiden;
use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\TindakLanjutInsiden;
use App\Models\User;
use App\Services\TindakLanjut\TindakLanjutService;
use App\Support\PatroliPeriode;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TindakLanjutOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_is_sorted_by_priority_and_deskripsi_is_item_bahaya(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $satpam = User::factory()->create(['role' => 'Satpam']);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Gedung A']);
        $periode = PatroliPeriode::keyFromDate(now());

        $laporan = LaporanInsiden::query()->create([
            'satpam_id' => $satpam->id,
            'lokasi_id' => $lokasi->id,
            'jenis_insiden' => 'Kebakaran',
            'tanggal_waktu' => now()->subHour(),
            'kronologi' => 'Api kecil di panel listrik.',
            'foto_path' => null,
        ]);

        TindakLanjutInsiden::query()->create([
            'laporan_insiden_id' => $laporan->id,
            'petugas_id' => null,
            'status_perbaikan' => 'Dalam Proses',
            'tanggal_tindakan' => null,
        ]);

        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Gedung A',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $items = [
            ['nama' => 'Bahaya Rendah', 'p' => 1, 's' => 2, 'level' => 'Rendah', 'skor' => 2],
            ['nama' => 'Bahaya Sedang', 'p' => 2, 's' => 3, 'level' => 'Sedang', 'skor' => 6],
            ['nama' => 'Bahaya Tinggi', 'p' => 3, 's' => 3, 'level' => 'Tinggi', 'skor' => 9],
            ['nama' => 'Bahaya Sangat Tinggi', 'p' => 5, 's' => 5, 'level' => 'Sangat Tinggi', 'skor' => 25],
        ];

        foreach ($items as $row) {
            $itemChecklist = ItemChecklist::query()->create([
                'master_checklist_id' => $checklist->id,
                'nama_item' => $row['nama'],
                'probability' => $row['p'],
                'severity' => $row['s'],
                'skor_risiko' => $row['skor'],
                'level_risiko' => $row['level'],
                'status' => 'Aktif',
            ]);

            $inspeksi = InspeksiK3l::query()->create([
                'petugas_id' => $petugas->id,
                'lokasi_id' => $lokasi->id,
                'master_checklist_id' => $checklist->id,
                'tanggal_inspeksi' => now()->subDays(1),
                'total_item' => 1,
                'item_sesuai' => 0,
                'item_tidak_sesuai' => 1,
                'persentase_kepatuhan' => 0,
            ]);

            DetailInspeksi::query()->create([
                'inspeksi_k3l_id' => $inspeksi->id,
                'item_checklist_id' => $itemChecklist->id,
                'status' => DetailInspeksi::STATUS_TIDAK,
                'analisa_risiko' => 'Analisa',
                'rekomendasi' => 'Rekomendasi',
                'foto_path' => null,
                'skor_risiko_hasil' => $row['skor'],
                'level_risiko_hasil' => $row['level'],
            ]);
        }

        $list = app(TindakLanjutService::class)->listItemsForPeriode($periode);

        $this->assertNotEmpty($list);
        $this->assertSame('insiden', $list[0]['ref_type']);
        $this->assertSame('Darurat', $list[0]['risiko']);

        $inspeksiOnly = array_values(array_filter($list, fn ($i) => $i['ref_type'] === 'inspeksi'));
        $this->assertSame('Sangat Tinggi', $inspeksiOnly[0]['risiko']);
        $this->assertSame('Bahaya Sangat Tinggi', $inspeksiOnly[0]['deskripsi']);
        $this->assertSame('Tinggi', $inspeksiOnly[1]['risiko']);
        $this->assertSame('Sedang', $inspeksiOnly[2]['risiko']);
        $this->assertSame('Rendah', $inspeksiOnly[3]['risiko']);
    }

    public function test_open_items_carry_over_to_later_periode(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist A',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $itemChecklist = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Kabel terkelupas',
            'probability' => 3,
            'severity' => 3,
            'skor_risiko' => 9,
            'level_risiko' => 'Tinggi',
            'status' => 'Aktif',
        ]);

        $originDate = Carbon::create(2026, 2, 15, 10, 0, 0);
        $periodeAsal = PatroliPeriode::keyFromDate($originDate);
        $periodeBerikut = '2026-2';

        $inspeksi = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => $originDate,
            'total_item' => 1,
            'item_sesuai' => 0,
            'item_tidak_sesuai' => 1,
            'persentase_kepatuhan' => 0,
        ]);

        DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => $inspeksi->id,
            'item_checklist_id' => $itemChecklist->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Analisa',
            'rekomendasi' => 'Rekomendasi',
            'foto_path' => null,
            'skor_risiko_hasil' => 9,
            'level_risiko_hasil' => 'Tinggi',
        ]);

        $service = app(TindakLanjutService::class);

        $asal = $service->listItemsForPeriode($periodeAsal);
        $this->assertCount(1, $asal);
        $this->assertFalse($asal[0]['is_carry_over']);

        $carry = $service->listItemsForPeriode($periodeBerikut);
        $this->assertCount(1, $carry);
        $this->assertTrue($carry[0]['is_carry_over']);
        $this->assertSame($periodeAsal, $carry[0]['periode_asal']);
    }

    public function test_selesai_item_visible_from_origin_through_completion_periode(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist B',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $itemChecklist = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Pintu darurat tertutup',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
        ]);

        $originDate = Carbon::create(2026, 2, 10, 9, 0, 0);
        $selesaiDate = Carbon::create(2026, 6, 5, 15, 0, 0);
        $periodeAsal = PatroliPeriode::keyFromDate($originDate);
        $periodeSelesai = PatroliPeriode::keyFromDate($selesaiDate);
        $periodeSetelahSelesai = '2026-3';

        $inspeksi = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => $originDate,
            'total_item' => 1,
            'item_sesuai' => 0,
            'item_tidak_sesuai' => 1,
            'persentase_kepatuhan' => 0,
        ]);

        $detail = DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => $inspeksi->id,
            'item_checklist_id' => $itemChecklist->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Analisa',
            'rekomendasi' => 'Rekomendasi',
            'foto_path' => null,
            'skor_risiko_hasil' => 6,
            'level_risiko_hasil' => 'Sedang',
        ]);

        \App\Models\TindakLanjutInspeksi::query()->create([
            'detail_inspeksi_id' => $detail->id,
            'petugas_id' => $petugas->id,
            'status_perbaikan' => 'Selesai',
            'tanggal_tindakan' => $selesaiDate->copy()->subDay(),
            'tanggal_selesai' => $selesaiDate,
            'catatan_perbaikan' => 'Sudah diperbaiki',
        ]);

        $service = app(TindakLanjutService::class);

        $asal = $service->listItemsForPeriode($periodeAsal);
        $this->assertCount(1, $asal);
        $this->assertSame('Selesai', $asal[0]['status']);

        $this->assertCount(1, $service->listItemsForPeriode($periodeSelesai));
        $this->assertCount(0, $service->listItemsForPeriode($periodeSetelahSelesai));
    }

    public function test_closed_origin_periode_keeps_snapshot_after_item_completed_later(): void
    {
        Storage::fake('local');

        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist F',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $itemChecklist = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Kabel terbuka',
            'probability' => 3,
            'severity' => 3,
            'skor_risiko' => 9,
            'level_risiko' => 'Tinggi',
            'status' => 'Aktif',
        ]);

        $originDate = Carbon::create(2026, 2, 12, 10, 0, 0);
        $periodeAsal = PatroliPeriode::keyFromDate($originDate);
        $periodeBerikut = '2026-2';

        $inspeksi = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => $originDate,
            'total_item' => 1,
            'item_sesuai' => 0,
            'item_tidak_sesuai' => 1,
            'persentase_kepatuhan' => 0,
        ]);

        $detail = DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => $inspeksi->id,
            'item_checklist_id' => $itemChecklist->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Analisa',
            'rekomendasi' => 'Rekomendasi',
            'foto_path' => null,
            'skor_risiko_hasil' => 9,
            'level_risiko_hasil' => 'Tinggi',
        ]);

        $service = app(TindakLanjutService::class);
        $this->assertCount(1, $service->listItemsForPeriode($periodeAsal));
        $this->assertSame('Menunggu Tindakan', $service->listItemsForPeriode($periodeAsal)[0]['status']);

        $this->actingAs($petugas)
            ->patchJson(route('tindak-lanjut.periode.selesai', ['periode' => $periodeAsal]))
            ->assertOk();

        Carbon::setTestNow(Carbon::create(2026, 6, 10, 12, 0, 0));

        $this->actingAs($petugas)
            ->postJson(route('tindak-lanjut.inspeksi.update', $detail), [
                'status' => 'Selesai',
                'catatan' => 'Diperbaiki di periode berikutnya',
            ])
            ->assertOk();

        $asalAfter = $service->listItemsForPeriode($periodeAsal);
        $this->assertCount(1, $asalAfter);
        $this->assertSame('Menunggu Tindakan', $asalAfter[0]['status']);
        $this->assertSame('Diperbaiki di periode berikutnya', $service->listItemsForPeriode($periodeBerikut)[0]['catatan'] ?? null);

        Carbon::setTestNow();
    }

    public function test_marking_inspeksi_selesai_does_not_generate_laporan_docx(): void
    {
        Storage::fake('local');

        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist C',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $itemChecklist = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'APAR kosong',
            'probability' => 4,
            'severity' => 4,
            'skor_risiko' => 16,
            'level_risiko' => 'Sangat Tinggi',
            'status' => 'Aktif',
        ]);

        $inspeksi = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => now(),
            'total_item' => 1,
            'item_sesuai' => 0,
            'item_tidak_sesuai' => 1,
            'persentase_kepatuhan' => 0,
        ]);

        $detail = DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => $inspeksi->id,
            'item_checklist_id' => $itemChecklist->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Risiko tinggi',
            'rekomendasi' => 'Isi ulang APAR',
            'foto_path' => null,
            'skor_risiko_hasil' => 16,
            'level_risiko_hasil' => 'Sangat Tinggi',
        ]);

        $this->actingAs($petugas)
            ->postJson(route('tindak-lanjut.inspeksi.update', $detail), [
                'status' => 'Selesai',
                'catatan' => 'APAR sudah diisi',
            ])
            ->assertOk()
            ->assertJsonMissing(['laporan_generated']);

        $this->assertSame(0, LaporanGenerated::query()->count());
    }

    public function test_marking_periode_selesai_generates_rekap_laporan_even_with_open_items(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist D',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $periode = PatroliPeriode::keyFromDate(now());

        $selesaiItem = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Lampu mati',
            'probability' => 2,
            'severity' => 2,
            'skor_risiko' => 4,
            'level_risiko' => 'Rendah',
            'status' => 'Aktif',
        ]);

        $openItem = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Kabel terkelupas',
            'probability' => 3,
            'severity' => 3,
            'skor_risiko' => 9,
            'level_risiko' => 'Tinggi',
            'status' => 'Aktif',
        ]);

        $inspeksiSelesai = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => now(),
            'total_item' => 1,
            'item_sesuai' => 0,
            'item_tidak_sesuai' => 1,
            'persentase_kepatuhan' => 0,
        ]);

        $fotoDokumentasi = 'patroli/temuan/demo-dokumentasi.jpg';
        $dokumentasiUpload = \Illuminate\Http\UploadedFile::fake()->image('dokumentasi.jpg', 640, 480);
        Storage::disk('public')->put($fotoDokumentasi, $dokumentasiUpload->getContent());

        $detailSelesai = DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => $inspeksiSelesai->id,
            'item_checklist_id' => $selesaiItem->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Analisa',
            'rekomendasi' => 'Rekomendasi',
            'foto_path' => $fotoDokumentasi,
            'skor_risiko_hasil' => 4,
            'level_risiko_hasil' => 'Rendah',
        ]);

        $inspeksiOpen = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => now(),
            'total_item' => 1,
            'item_sesuai' => 0,
            'item_tidak_sesuai' => 1,
            'persentase_kepatuhan' => 0,
        ]);

        $detailOpen = DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => $inspeksiOpen->id,
            'item_checklist_id' => $openItem->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Analisa kabel',
            'rekomendasi' => 'Ganti kabel',
            'foto_path' => null,
            'skor_risiko_hasil' => 9,
            'level_risiko_hasil' => 'Tinggi',
        ]);

        $satpam = User::factory()->create([
            'role' => 'Satpam',
            'nama_lengkap' => 'Budi Satpam',
        ]);

        LaporanInsiden::query()->create([
            'satpam_id' => $satpam->id,
            'lokasi_id' => $lokasi->id,
            'jenis_insiden' => 'Kebakaran',
            'tanggal_waktu' => now(),
            'kronologi' => 'Api kecil di panel listrik ruang genset.',
            'korban' => 'Tidak ada',
            'foto_path' => null,
        ]);

        $this->actingAs($petugas)
            ->post(route('tindak-lanjut.inspeksi.update', $detailSelesai), [
                'status' => 'Selesai',
                'catatan' => 'Lampu sudah diganti',
                'foto' => \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg', 640, 480),
            ])
            ->assertOk();

        $this->actingAs($petugas)
            ->patchJson(route('tindak-lanjut.periode.selesai', ['periode' => $periode]))
            ->assertOk()
            ->assertJsonPath('status', 'Selesai');

        $generated = LaporanGenerated::query()
            ->where('jenis_laporan', LaporanGenerated::JENIS_TINDAK_LANJUT)
            ->where('user_id', $petugas->id)
            ->first();

        $this->assertNotNull($generated);
        Storage::disk('local')->assertExists($generated->file_path_docx);
        $this->assertSame(1, LaporanGenerated::query()->count());

        $docxPath = Storage::disk('local')->path($generated->file_path_docx);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($docxPath));
        $xml = $zip->getFromName('word/document.xml');

        $hasMedia = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with($zip->getNameIndex($i), 'word/media/')) {
                $hasMedia = true;
                break;
            }
        }
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringContainsString('LAPORAN REKAPITULASI TINDAK LANJUT K3LH', $xml);
        $this->assertStringContainsString('POLITEKNIK NEGERI BANYUWANGI', $xml);
        $this->assertStringContainsString('Jl. Raya Jember kilometer 13 Labanasem', $xml);
        $this->assertStringContainsString('PENDAHULUAN', $xml);
        $this->assertStringContainsString('URAIAN', $xml);
        $this->assertStringContainsString('KETERANGAN', $xml);
        $this->assertStringContainsString('Item temuan / bahaya', $xml);
        $this->assertStringContainsString('Analisa risiko', $xml);
        $this->assertStringContainsString('Rekomendasi', $xml);
        $this->assertStringContainsString('Lampu mati', $xml);
        $this->assertStringContainsString('Kabel terkelupas', $xml);
        $this->assertStringContainsString('Jenis insiden', $xml);
        $this->assertStringContainsString('Kebakaran', $xml);
        $this->assertStringContainsString('Kronologi kejadian', $xml);
        $this->assertStringContainsString('Api kecil di panel listrik ruang genset.', $xml);
        $this->assertStringContainsString('Pelapor (Satpam)', $xml);
        $this->assertStringContainsString('Budi Satpam', $xml);
        $this->assertStringContainsString('Foto Dokumentasi Patroli', $xml);
        $this->assertStringContainsString('Foto Bukti Perbaikan', $xml);
        $this->assertTrue($hasMedia, 'DOCX rekap tindak lanjut harus menyertakan foto.');

        $this->actingAs($petugas)
            ->postJson(route('tindak-lanjut.inspeksi.update', $detailSelesai), [
                'status' => 'Dalam Proses',
                'catatan' => 'Coba ubah lagi',
            ])
            ->assertStatus(422);

        $this->actingAs($petugas)
            ->postJson(route('tindak-lanjut.inspeksi.update', $detailOpen), [
                'status' => 'Dalam Proses',
                'catatan' => 'Sedang diperbaiki',
            ])
            ->assertOk();
    }

    public function test_closed_periode_laporan_is_not_regenerated_on_preview(): void
    {
        Storage::fake('local');

        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist E',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $periode = PatroliPeriode::keyFromDate(now());

        $itemChecklist = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Pintu rusak',
            'probability' => 3,
            'severity' => 3,
            'skor_risiko' => 9,
            'level_risiko' => 'Tinggi',
            'status' => 'Aktif',
        ]);

        $inspeksi = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => now(),
            'total_item' => 1,
            'item_sesuai' => 0,
            'item_tidak_sesuai' => 1,
            'persentase_kepatuhan' => 0,
        ]);

        $detail = DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => $inspeksi->id,
            'item_checklist_id' => $itemChecklist->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Analisa awal',
            'rekomendasi' => 'Perbaiki pintu',
            'foto_path' => null,
            'skor_risiko_hasil' => 9,
            'level_risiko_hasil' => 'Tinggi',
        ]);

        $this->actingAs($petugas)
            ->patchJson(route('tindak-lanjut.periode.selesai', ['periode' => $periode]))
            ->assertOk();

        $laporan = LaporanGenerated::query()->firstOrFail();
        $originalHash = md5(Storage::disk('local')->get($laporan->file_path_docx));

        $this->actingAs($petugas)
            ->postJson(route('tindak-lanjut.inspeksi.update', $detail), [
                'status' => 'Dalam Proses',
                'catatan' => 'Sudah dikerjakan di periode berikutnya',
            ])
            ->assertOk();

        $this->actingAs($petugas)
            ->get(route('laporan.preview', $laporan))
            ->assertOk();

        $afterPreviewHash = md5(Storage::disk('local')->get($laporan->file_path_docx));
        $this->assertSame($originalHash, $afterPreviewHash);
    }
}
