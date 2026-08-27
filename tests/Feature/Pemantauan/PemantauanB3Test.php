<?php

namespace Tests\Feature\Pemantauan;

use App\Models\JenisLimbahB3;
use App\Models\LaporanLimbahB3;
use App\Models\LogbookLimbahB3;
use App\Models\ManifestLimbahB3;
use App\Models\User;
use App\Support\B3Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemantauanB3Test extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_view_b3_page(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->get(route('pemantauan.b3'))
            ->assertOk()
            ->assertSee('Pemantauan Limbah B3');
    }

    public function test_kalab_can_view_b3_page(): void
    {
        $user = User::factory()->create(['role' => 'Kalab']);

        $this->actingAs($user)
            ->get(route('pemantauan.b3'))
            ->assertOk();
    }

    public function test_store_rejects_incomplete_jenis_row(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $payload = $this->samplePayload(includeJenis: false, includeManifest: false);
        $payload['jenis_list'] = [
            [
                'nama_limbah' => 'Oli Bekas',
                'kode_limbah' => '',
                'sumber_limbah' => 'Bengkel',
                'karakteristik' => 'Beracun',
                'pengemasan' => 'Drum',
                'masa_simpan_hari' => 90,
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jenis_list.0.kode_limbah']);
    }

    public function test_petugas_can_create_laporan_b3_with_logbook_only(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $payload = $this->samplePayload(includeJenis: false, includeManifest: false);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $payload)
            ->assertOk()
            ->assertJsonPath('listItem.jenisCount', 0)
            ->assertJsonPath('listItem.manifestCount', 0)
            ->assertJsonPath('listItem.logbookCount', 1);

        $laporan = LaporanLimbahB3::query()->firstOrFail();
        $this->assertSame(0, JenisLimbahB3::query()->where('laporan_limbah_b3_id', $laporan->id)->count());
        $this->assertSame(1, LogbookLimbahB3::query()->where('laporan_limbah_b3_id', $laporan->id)->count());
    }

    public function test_petugas_can_create_laporan_b3_without_manifest(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $payload = $this->samplePayload(includeManifest: false);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $payload)
            ->assertOk()
            ->assertJsonPath('listItem.manifestCount', 0);

        $laporan = LaporanLimbahB3::query()->firstOrFail();
        $this->assertSame(0, ManifestLimbahB3::query()->where('laporan_limbah_b3_id', $laporan->id)->count());
        $this->assertSame(1, LogbookLimbahB3::query()->where('laporan_limbah_b3_id', $laporan->id)->count());
    }

    public function test_petugas_can_create_laporan_b3(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $payload = $this->samplePayload();

        $response = $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $payload);

        $response->assertOk()
            ->assertJsonPath('listItem.semester', 1)
            ->assertJsonPath('listItem.jenisCount', 1)
            ->assertJsonPath('listItem.logbookCount', 1)
            ->assertJsonPath('listItem.manifestCount', 1);

        $laporan = LaporanLimbahB3::query()->first();
        $this->assertNotNull($laporan);
        $this->assertSame($user->id, $laporan->petugas_id);
        $this->assertSame(1, $laporan->semester);
        $this->assertSame((int) date('Y'), $laporan->tahun);
        $this->assertSame(B3Semester::STATUS_BERLANGSUNG, $laporan->status);

        $this->assertSame(1, JenisLimbahB3::query()->where('laporan_limbah_b3_id', $laporan->id)->count());
        $this->assertSame(1, LogbookLimbahB3::query()->where('laporan_limbah_b3_id', $laporan->id)->count());
        $this->assertSame(1, ManifestLimbahB3::query()->where('laporan_limbah_b3_id', $laporan->id)->count());
    }

    public function test_duplicate_semester_tahun_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        LaporanLimbahB3::factory()->create([
            'petugas_id' => $user->id,
            'semester' => 1,
            'tahun' => (int) date('Y'),
        ]);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['semester']);
    }

    public function test_duplicate_nomor_manifest_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $tahun = (int) date('Y');

        LaporanLimbahB3::factory()->create([
            'petugas_id' => $user->id,
            'semester' => 2,
            'tahun' => $tahun,
        ]);

        ManifestLimbahB3::query()->create([
            'laporan_limbah_b3_id' => LaporanLimbahB3::query()->first()->id,
            'nomor_manifest' => 'MAN-001',
            'tanggal_manifest' => "{$tahun}-07-01",
            'nama_pengirim' => 'PT A',
            'alamat_pengirim' => 'Alamat A',
            'kode_limbah' => 'A337-1',
            'nama_limbah' => 'Oli Bekas',
            'karakteristik_limbah' => 'Beracun',
            'jenis_kemasan' => 'Drum',
            'jumlah_kemasan' => 1,
            'jumlah_limbah_ton' => 0.5,
            'tujuan_pengangkutan' => 'Pengelola B3',
            'nama_pengangkut' => 'Transporter X',
            'alamat_pengangkut' => 'Alamat Transporter',
            'nama_penerima' => 'Penerima Y',
            'alamat_penerima' => 'Alamat Penerima',
            'jenis_pengelolaan' => 'Pengolahan',
        ]);

        $payload = $this->samplePayload();
        $payload['manifest_list'][0]['nomor_manifest'] = 'MAN-001';

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['manifest_list.0.nomor_manifest']);
    }

    public function test_petugas_can_update_laporan_b3(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanLimbahB3::query()->firstOrFail();
        $payload = $this->samplePayload();
        $payload['jenis_list'][0]['nama_limbah'] = 'Baterai Bekas';

        $this->actingAs($user)
            ->putJson(route('pemantauan.b3.update', $laporan), $payload)
            ->assertOk()
            ->assertJsonPath('data.jenisList.0.nama_limbah', 'Baterai Bekas');

        $this->assertDatabaseHas('jenis_limbah_b3', [
            'laporan_limbah_b3_id' => $laporan->id,
            'nama_limbah' => 'Baterai Bekas',
        ]);
    }

    public function test_petugas_can_show_laporan_b3(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanLimbahB3::query()->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('pemantauan.b3.show', $laporan))
            ->assertOk()
            ->assertJsonPath('data.id', $laporan->id)
            ->assertJsonPath('data.jenisList.0.kode_limbah', 'A337-1');
    }

    public function test_kalab_can_show_but_cannot_store(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $kalab = User::factory()->create(['role' => 'Kalab']);

        $this->actingAs($petugas)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanLimbahB3::query()->firstOrFail();

        $this->actingAs($kalab)
            ->getJson(route('pemantauan.b3.show', $laporan))
            ->assertOk();

        $this->actingAs($kalab)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertRedirect(route('dashboard'));
    }

    public function test_store_accepts_logbook_with_empty_entries_on_other_months(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $tahun = (int) date('Y');
        $payload = $this->samplePayload(includeJenis: false, includeManifest: false);
        $payload['logbook_bulan_list'] = [
            [
                'nama' => 'Januari',
                'entries' => [
                    [
                        'tanggal_masuk' => "{$tahun}-01-10",
                        'tanggal_keluar' => null,
                        'jenis_limbah' => 'Oli Bekas',
                        'sumber_limbah' => 'Bengkel',
                        'jumlah_masuk_kg' => 25.5,
                        'jumlah_keluar_kg' => null,
                        'pengemasan' => 'Drum',
                    ],
                ],
            ],
            ['nama' => 'Februari', 'entries' => []],
            ['nama' => 'Maret', 'entries' => []],
            ['nama' => 'April', 'entries' => []],
            ['nama' => 'Mei', 'entries' => []],
            ['nama' => 'Juni', 'entries' => []],
        ];

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $payload)
            ->assertOk();
    }

    public function test_mark_laporan_selesai_generates_swapantau_docx(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanLimbahB3::query()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('pemantauan.b3.selesai', $laporan))
            ->assertOk()
            ->assertJsonPath('listItem.status', B3Semester::STATUS_SELESAI);

        $generated = \App\Models\LaporanGenerated::query()
            ->where('jenis_laporan', \App\Models\LaporanGenerated::JENIS_B3)
            ->first();

        $this->assertNotNull($generated);
        $this->assertNotNull($generated->file_path_docx);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($generated->file_path_docx);

        $docxPath = \Illuminate\Support\Facades\Storage::disk('local')->path($generated->file_path_docx);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true);

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('LAPORAN PEMANTAUAN PENGELOLAAN', $documentXml);
        $this->assertStringContainsString('LIMBAH BAHAN BERBAHAYA DAN BERACUN', $documentXml);
        $this->assertStringContainsString('PERIODE SEMESTER I (JANUARI', $documentXml);
        $this->assertStringNotContainsString('PERIODE SEMESTER II (JULI', $documentXml);
        $this->assertStringContainsString('Kelurahan/Desa Labanasem', $documentXml);
        $this->assertStringContainsString('A. IDENTITAS PERUSAHAAN', $documentXml);
        $this->assertStringContainsString('B. LOKASI USAHA DAN ATAU KEGIATAN', $documentXml);
        $this->assertStringContainsString('Nomor Dokumen Rincian Teknis Limbah B3', $documentXml);
        $this->assertStringContainsString('Penanggung Jawab', $documentXml);
        $this->assertStringContainsString('Batas Utara', $documentXml);
        $this->assertStringContainsString('MASUKNYA LIMBAH B3 KE TPS', $documentXml);
        $this->assertStringContainsString('KELUARNYA LIMBAH B3 DARI TPS', $documentXml);
        $this->assertStringContainsString('Oli Bekas', $documentXml);
        $this->assertStringContainsString('MANIFES LIMBAH BAHAN BERBAHAYA DAN BERACUN', $documentXml);
        $this->assertStringContainsString('Informasi Tentang Pengirim Limbah B3', $documentXml);
        $this->assertStringContainsString('Nomor Manifes', $documentXml);
    }

    public function test_petugas_can_mark_laporan_selesai(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanLimbahB3::query()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('pemantauan.b3.selesai', $laporan))
            ->assertOk()
            ->assertJsonPath('listItem.status', B3Semester::STATUS_SELESAI);

        $this->assertSame(B3Semester::STATUS_SELESAI, $laporan->fresh()->status);
    }

    public function test_petugas_can_delete_laporan_b3(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanLimbahB3::query()->firstOrFail();

        $this->actingAs($user)
            ->deleteJson(route('pemantauan.b3.destroy', $laporan))
            ->assertOk();

        $this->assertSame(0, LaporanLimbahB3::query()->count());
        $this->assertSame(0, JenisLimbahB3::query()->count());
        $this->assertSame(0, LogbookLimbahB3::query()->count());
        $this->assertSame(0, ManifestLimbahB3::query()->count());
    }

    public function test_store_rejects_empty_logbook_entries(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $payload = $this->samplePayload();
        $payload['logbook_bulan_list'] = [
            [
                'nama' => 'Januari',
                'entries' => [
                    [
                        'tanggal_masuk' => '',
                        'tanggal_keluar' => '',
                        'jenis_limbah' => '',
                        'sumber_limbah' => '',
                        'jumlah_masuk_kg' => '',
                        'jumlah_keluar_kg' => '',
                        'pengemasan' => '',
                    ],
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('pemantauan.b3.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logbook_bulan_list']);
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePayload(bool $includeJenis = true, bool $includeManifest = true): array
    {
        $tahun = (int) date('Y');

        $payload = [
            'semester' => 1,
            'tahun' => $tahun,
            'logbook_bulan_list' => [
                [
                    'nama' => 'Januari',
                    'entries' => [
                        [
                            'tanggal_masuk' => "{$tahun}-01-10",
                            'tanggal_keluar' => "{$tahun}-01-20",
                            'jenis_limbah' => 'Oli Bekas',
                            'sumber_limbah' => 'Bengkel',
                            'jumlah_masuk_kg' => 25.5,
                            'jumlah_keluar_kg' => 20,
                            'pengemasan' => 'Drum',
                        ],
                    ],
                ],
            ],
        ];

        if ($includeJenis) {
            $payload['jenis_list'] = [
                [
                    'nama_limbah' => 'Oli Bekas',
                    'kode_limbah' => 'A337-1',
                    'sumber_limbah' => 'Bengkel',
                    'karakteristik' => 'Beracun',
                    'pengemasan' => 'Drum',
                    'masa_simpan_hari' => 90,
                ],
            ];
        } else {
            $payload['jenis_list'] = [];
        }

        if ($includeManifest) {
            $payload['manifest_list'] = [
                [
                    'nomor_manifest' => 'MAN-'.fake()->unique()->numerify('####'),
                    'tanggal_manifest' => "{$tahun}-02-01",
                    'nama_pengirim' => 'PT Contoh',
                    'alamat_pengirim' => 'Jl. Contoh No. 1',
                    'kode_limbah' => 'A337-1',
                    'nama_limbah' => 'Oli Bekas',
                    'karakteristik_limbah' => 'Beracun',
                    'jenis_kemasan' => 'Drum',
                    'jumlah_kemasan' => 2,
                    'jumlah_limbah_ton' => 0.75,
                    'tujuan_pengangkutan' => 'Pengelola B3 Terdaftar',
                    'nama_pengangkut' => 'CV Transport',
                    'alamat_pengangkut' => 'Jl. Transport',
                    'nama_penerima' => 'PT Pengelola',
                    'alamat_penerima' => 'Jl. Pengelola',
                    'jenis_pengelolaan' => 'Pengolahan',
                ],
            ];
        } else {
            $payload['manifest_list'] = [];
        }

        return $payload;
    }
}
