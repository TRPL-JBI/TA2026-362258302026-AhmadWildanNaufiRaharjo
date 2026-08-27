<?php

namespace Tests\Feature\Patroli;

use App\Models\Apar;
use App\Models\DetailInspeksi;
use App\Models\InspeksiK3l;
use App\Models\ItemChecklist;
use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\PatroliLaporanPeriode;
use App\Models\PemeriksaanApar;
use App\Models\User;
use App\Services\Patroli\PatroliAparDraftBuilder;
use App\Support\PatroliPeriode;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatroliManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_resolve_lokasi_qr_and_store_inspeksi(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create([
            'nama_lokasi' => 'Gedung A',
            'jenis_lokasi' => 'Gedung',
        ]);

        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Gedung A',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $itemYa = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'APAR siap pakai',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
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

        $payload = json_encode(['type' => 'lokasi', 'id' => $lokasi->id], JSON_UNESCAPED_UNICODE);

        Log::spy();

        $this->actingAs($petugas)
            ->postJson(route('patroli.qr.resolve'), ['q' => $payload, 'scan_ms' => 1840])
            ->assertOk()
            ->assertJsonPath('section.nama', 'Gedung A')
            ->assertJsonPath('section.master_checklist_id', $checklist->id);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return $message === 'QR-SCAN'
                && $context['status'] === 'berhasil'
                && $context['type'] === 'lokasi'
                && $context['scan_ms'] === 1840
                && array_key_exists('resolve_ms', $context);
        })->once();

        $this->actingAs($petugas)
            ->postJson(route('patroli.qr.resolve'), ['q' => $lokasi->kode_lokasi])
            ->assertOk()
            ->assertJsonPath('section.nama', 'Gedung A')
            ->assertJsonPath('section.master_checklist_id', $checklist->id);

        Storage::fake('public');

        $fotoTemuan = UploadedFile::fake()->image('temuan.jpg', 640, 480);

        $this->actingAs($petugas)
            ->post(route('patroli.inspeksi.store'), [
                'sections' => [
                    [
                        'lokasi_id' => $lokasi->id,
                        'master_checklist_id' => $checklist->id,
                        'items' => [
                            ['item_checklist_id' => $itemYa->id, 'status' => 'ya'],
                            [
                                'item_checklist_id' => $itemTidak->id,
                                'status' => 'tidak',
                                'analisa_risiko' => 'Kabel tidak terlindung',
                                'rekomendasi' => 'Ganti kabel dan pasang pelindung',
                            ],
                        ],
                    ],
                ],
                'foto_item' => [
                    $itemTidak->id => $fotoTemuan,
                ],
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('data.temuan_count', 1)
            ->assertJsonPath('data.tindak_lanjut_count', 1);

        $this->assertDatabaseCount('inspeksi_k3l', 1);
        $this->assertDatabaseCount('detail_inspeksi', 2);
        $this->assertDatabaseCount('tindak_lanjut_inspeksi', 1);

        $inspeksi = InspeksiK3l::query()->first();
        $this->assertSame(2, $inspeksi->total_item);
        $this->assertSame(1, $inspeksi->item_sesuai);
        $this->assertSame(1, $inspeksi->item_tidak_sesuai);
        $this->assertEquals(50.0, (float) $inspeksi->persentase_kepatuhan);

        $detailTidak = DetailInspeksi::query()
            ->where('status', DetailInspeksi::STATUS_TIDAK)
            ->first();
        $this->assertNotNull($detailTidak);
        $this->assertSame('Sangat Tinggi', $detailTidak->level_risiko_hasil);
        $this->assertNotNull($detailTidak->foto_path);
        Storage::disk('public')->assertExists($detailTidak->foto_path);
        $this->assertDatabaseHas('tindak_lanjut_inspeksi', [
            'detail_inspeksi_id' => $detailTidak->id,
            'status_perbaikan' => 'Dalam Proses',
        ]);
    }

    public function test_apar_qr_on_temuan_page_redirects_to_apar_form(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'gedung TI']);
        $apar = Apar::factory()->forLokasi($lokasi)->create([
            'kode_apar' => 'APAR-GED-001',
        ]);

        $payload = json_encode([
            'type' => 'apar',
            'id' => $apar->id,
            'kode' => $apar->kode_apar,
        ], JSON_UNESCAPED_UNICODE);

        $this->actingAs($petugas)
            ->get(route('patroli.temuan', ['q' => $payload]))
            ->assertRedirect(route('patroli.apar', ['q' => $payload]));

        $this->actingAs($petugas)
            ->get(route('patroli.apar', ['q' => $payload]))
            ->assertOk()
            ->assertSee('APAR-GED-001')
            ->assertSee('gedung TI');
    }

    public function test_petugas_can_store_pemeriksaan_apar_and_update_inventaris(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Gedung B']);
        $apar = Apar::factory()->forLokasi($lokasi)->create([
            'status_kondisi' => null,
        ]);

        Storage::fake('public');

        $fotoApar = UploadedFile::fake()->image('apar-kondisi.jpg', 720, 720);

        $this->actingAs($petugas)
            ->post(route('patroli.apar.store'), [
                'pemeriksaan' => [
                    [
                        'apar_id' => $apar->id,
                        'kondisi_tabung' => 'Tabung baik, label jelas',
                        'kondisi_segel' => 'tersegel',
                    ],
                ],
                'foto_apar' => [
                    $apar->id => [$fotoApar],
                ],
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.periode', PatroliPeriode::keyFromDate(now()));

        $this->assertDatabaseCount('pemeriksaan_apar', 1);

        $pemeriksaan = PemeriksaanApar::query()->first();
        $this->assertNotNull($pemeriksaan->foto_path);
        Storage::disk('public')->assertExists($pemeriksaan->foto_path);

        $this->actingAs($petugas)
            ->post(route('patroli.apar.store'), [
                'pemeriksaan' => [
                    [
                        'apar_id' => Apar::factory()->forLokasi($lokasi)->create()->id,
                        'kondisi_tabung' => 'Tanpa foto',
                        'kondisi_segel' => 'tersegel',
                    ],
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $tanpaFoto = PemeriksaanApar::query()->where('kondisi_tabung', 'Tanpa foto')->first();
        $this->assertNull($tanpaFoto->foto_path);

        $apar->refresh();
        $this->assertSame(Apar::KONDISI_BAIK_TERSEGEL, $apar->status_kondisi);

        $this->actingAs($petugas)
            ->get(route('patroli.riwayat'))
            ->assertOk()
            ->assertSee('Pemantauan APAR');
    }

    public function test_continue_apar_loads_stored_foto_kondisi(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Gedung C']);
        $apar = Apar::factory()->forLokasi($lokasi)->create();
        $fotoPath = 'patroli/apar/2026/05/test-continue.jpg';

        Storage::fake('public');
        Storage::disk('public')->put($fotoPath, 'jpeg-bytes');

        PemeriksaanApar::query()->create([
            'petugas_id' => $petugas->id,
            'apar_id' => $apar->id,
            'tanggal_pemeriksaan' => now(),
            'kondisi_tabung' => 'Baik',
            'kondisi_segel' => 'Tersegel',
            'foto_path' => $fotoPath,
        ]);

        $periode = PatroliPeriode::keyFromDate(now());
        $sections = app(PatroliAparDraftBuilder::class)->lokasiSectionsForContinue($petugas, $periode);

        $this->assertCount(1, $sections[0]['aparList'][0]['fotoKondisi']);
        $this->assertTrue($sections[0]['aparList'][0]['fotoKondisi'][0]['existing']);
        $this->assertStringContainsString('storage/', $sections[0]['aparList'][0]['fotoKondisi'][0]['preview']);
        $this->assertStringContainsString('test-continue.jpg', $sections[0]['aparList'][0]['fotoKondisi'][0]['preview']);
        $this->assertNotEmpty($sections[0]['aparList'][0]['tanggalPemeriksaan']);
        $this->assertSame(
            now()->translatedFormat('d F Y'),
            $sections[0]['aparList'][0]['tanggalPemeriksaan'],
        );

        $this->actingAs($petugas)
            ->get(route('patroli.apar', ['continue_periode' => $periode]))
            ->assertOk()
            ->assertSee('test-continue.jpg', false);
    }

    public function test_continue_apar_save_removes_deleted_lokasi_from_pemeriksaan(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasiA = Lokasi::factory()->create(['nama_lokasi' => 'Gedung A']);
        $lokasiB = Lokasi::factory()->create(['nama_lokasi' => 'Gedung B']);
        $aparA = Apar::factory()->forLokasi($lokasiA)->create();
        $aparB = Apar::factory()->forLokasi($lokasiB)->create();
        $periode = PatroliPeriode::keyFromDate(now());

        $pemeriksaanA = PemeriksaanApar::query()->create([
            'petugas_id' => $petugas->id,
            'apar_id' => $aparA->id,
            'tanggal_pemeriksaan' => now(),
            'kondisi_tabung' => 'Baik A',
            'kondisi_segel' => 'Tersegel',
        ]);
        $pemeriksaanB = PemeriksaanApar::query()->create([
            'petugas_id' => $petugas->id,
            'apar_id' => $aparB->id,
            'tanggal_pemeriksaan' => now(),
            'kondisi_tabung' => 'Baik B',
            'kondisi_segel' => 'Tersegel',
        ]);

        $this->actingAs($petugas)
            ->withSession(['patroli_continue_periode' => $periode])
            ->postJson(route('patroli.apar.store'), [
                'pemeriksaan' => [
                    [
                        'apar_id' => $aparB->id,
                        'pemeriksaan_id' => $pemeriksaanB->id,
                        'kondisi_tabung' => 'Baik B diperbarui',
                        'kondisi_segel' => 'tersegel',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.deleted', 1);

        $this->assertDatabaseMissing('pemeriksaan_apar', ['id' => $pemeriksaanA->id]);
        $this->assertDatabaseHas('pemeriksaan_apar', [
            'id' => $pemeriksaanB->id,
            'kondisi_tabung' => 'Baik B diperbarui',
        ]);
    }

    public function test_petugas_can_view_riwayat_detail_and_continue_temuan(): void
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
            ->get(route('patroli.riwayat', ['periode' => $periode]))
            ->assertOk()
            ->assertSee('Patroli')
            ->assertSee('Pantau progress inspeksi temuan bahaya dan pemeriksaan APAR per caturwulan')
            ->assertSee('APAR siap')
            ->assertSee('Lihat Terinspeksi');

        $this->actingAs($petugas)
            ->get(route('patroli.riwayat.temuan.lanjutkan', ['periode' => $periode]))
            ->assertRedirect(route('patroli.riwayat', ['periode' => $periode]));

        $this->actingAs($petugas)
            ->get(route('patroli.temuan', ['continue_periode' => $periode]))
            ->assertOk()
            ->assertSee('Checklist Inspeksi')
            ->assertSee('APAR siap')
            ->assertSee('Ya, Sesuai')
            ->assertDontSee('tambah lokasi baru via scan', false);
    }

    public function test_riwayat_honors_explicit_periode_over_continue_session(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        $lokasiCw1 = Lokasi::factory()->create([
            'nama_lokasi' => 'Gedung Periode Satu',
            'jenis_lokasi' => 'Gedung',
        ]);
        $checklistCw1 = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist CW I',
            'lokasi_id' => $lokasiCw1->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $itemCw1 = ItemChecklist::query()->create([
            'master_checklist_id' => $checklistCw1->id,
            'nama_item' => 'Item khusus CW I',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
        ]);

        InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasiCw1->id,
            'master_checklist_id' => $checklistCw1->id,
            'tanggal_inspeksi' => '2026-03-15 10:00:00',
            'total_item' => 1,
            'item_sesuai' => 1,
            'item_tidak_sesuai' => 0,
            'persentase_kepatuhan' => 100,
        ]);

        $lokasiCw2 = Lokasi::factory()->create([
            'nama_lokasi' => 'Gedung Periode Dua',
            'jenis_lokasi' => 'Gedung',
        ]);
        $checklistCw2 = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist CW II',
            'lokasi_id' => $lokasiCw2->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $itemCw2 = ItemChecklist::query()->create([
            'master_checklist_id' => $checklistCw2->id,
            'nama_item' => 'Item khusus CW II',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
        ]);

        InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasiCw2->id,
            'master_checklist_id' => $checklistCw2->id,
            'tanggal_inspeksi' => '2026-06-10 10:00:00',
            'total_item' => 1,
            'item_sesuai' => 1,
            'item_tidak_sesuai' => 0,
            'persentase_kepatuhan' => 100,
        ]);

        DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => InspeksiK3l::query()->where('lokasi_id', $lokasiCw1->id)->value('id'),
            'item_checklist_id' => $itemCw1->id,
            'status' => DetailInspeksi::STATUS_YA,
        ]);
        DetailInspeksi::query()->create([
            'inspeksi_k3l_id' => InspeksiK3l::query()->where('lokasi_id', $lokasiCw2->id)->value('id'),
            'item_checklist_id' => $itemCw2->id,
            'status' => DetailInspeksi::STATUS_YA,
        ]);

        $this->actingAs($petugas)
            ->withSession(['patroli_continue_periode' => '2026-2'])
            ->get(route('patroli.riwayat', ['periode' => '2026-1']))
            ->assertOk()
            ->assertSee('value="2026-1" selected', false)
            ->assertSee('\u0022periode\u0022:\u00222026-1\u0022', false)
            ->assertSessionMissing('patroli_continue_periode');

        $this->actingAs($petugas)
            ->get(route('patroli.riwayat', ['periode' => '2026-2']))
            ->assertOk()
            ->assertSee('value="2026-2" selected', false)
            ->assertSee('\u0022periode\u0022:\u00222026-2\u0022', false);
    }

    public function test_petugas_can_update_temuan_from_continue_view(): void
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
            'nama_item' => 'Pintu darurat',
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

        $inspeksi = InspeksiK3l::query()->firstOrFail();

        Storage::fake('public');

        $this->actingAs($petugas)->post(route('patroli.inspeksi.store'), [
            'sections' => [[
                'inspeksi_id' => $inspeksi->id,
                'lokasi_id' => $lokasi->id,
                'master_checklist_id' => $checklist->id,
                'items' => [[
                    'item_checklist_id' => $item->id,
                    'status' => 'tidak',
                    'analisa_risiko' => 'Rusak',
                    'rekomendasi' => 'Perbaiki',
                ]],
            ]],
            'foto_item' => [$item->id => UploadedFile::fake()->image('temuan.jpg')],
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $detail = DetailInspeksi::query()->where('inspeksi_k3l_id', $inspeksi->id)->firstOrFail();

        $this->assertSame(DetailInspeksi::STATUS_TIDAK, $detail->status);
        $this->assertSame('Rusak', $detail->analisa_risiko);
        $this->assertDatabaseCount('inspeksi_k3l', 1);
    }

    public function test_petugas_can_delete_riwayat_apar_by_date(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();
        $apar = Apar::factory()->forLokasi($lokasi)->create();
        $periode = PatroliPeriode::keyFromDate(now());

        PemeriksaanApar::query()->create([
            'petugas_id' => $petugas->id,
            'apar_id' => $apar->id,
            'tanggal_pemeriksaan' => now(),
            'kondisi_tabung' => 'Baik',
            'kondisi_segel' => 'Tersegel',
        ]);

        $this->actingAs($petugas)
            ->deleteJson(route('patroli.riwayat.apar.destroy', ['periode' => $periode]))
            ->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->assertDatabaseCount('pemeriksaan_apar', 0);
    }

    public function test_petugas_can_mark_riwayat_temuan_selesai(): void
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

        $this->actingAs($petugas)
            ->get(route('patroli.riwayat', ['periode' => $periode]))
            ->assertOk()
            ->assertSee('Selesai');

        $this->actingAs($petugas)
            ->get(route('patroli.temuan', ['continue_periode' => $periode]))
            ->assertOk()
            ->assertSee('Mode lihat', false)
            ->assertSee('APAR siap');
    }

    public function test_satpam_cannot_store_patroli(): void
    {
        $satpam = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($satpam)
            ->postJson(route('patroli.inspeksi.store'), ['sections' => []])
            ->assertRedirect(route('dashboard'));
    }

    public function test_riwayat_patroli_menampilkan_semua_lokasi_termasuk_laboratorium(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        Lokasi::factory()->create(['nama_lokasi' => 'Gedung Patroli', 'jenis_lokasi' => 'Gedung']);
        Lokasi::factory()->create(['nama_lokasi' => 'Ruangan Patroli', 'jenis_lokasi' => 'Ruangan']);
        Lokasi::factory()->create(['nama_lokasi' => 'Lab Kimia', 'jenis_lokasi' => 'Laboratorium']);

        $this->actingAs($petugas)
            ->get(route('patroli.riwayat'))
            ->assertOk()
            ->assertSee('Gedung Patroli')
            ->assertSee('Ruangan Patroli')
            ->assertSee('Lab Kimia');
    }

    public function test_riwayat_checklist_modal_menampilkan_lokasi_gedung_tanpa_checklist(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        Lokasi::factory()->create([
            'nama_lokasi' => 'Gedung Baru',
            'jenis_lokasi' => 'Gedung',
        ]);
        Lokasi::factory()->create([
            'nama_lokasi' => 'Lab Tanpa Checklist',
            'jenis_lokasi' => 'Laboratorium',
        ]);

        $overview = app(\App\Services\Patroli\PatroliRiwayatOverviewService::class)
            ->overview($petugas, PatroliPeriode::keyFromDate(now()));

        $labels = collect($overview['temuan']['lokasi_tanpa_checklist'])->pluck('label')->all();

        $this->assertContains('Gedung Baru', $labels);
        $this->assertContains('Lab Tanpa Checklist', $labels);
    }

    public function test_petugas_dapat_tambah_checklist_laboratorium_dari_riwayat_patroli(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create([
            'nama_lokasi' => 'Lab Biologi',
            'jenis_lokasi' => 'Laboratorium',
        ]);
        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->postJson(route('patroli.riwayat.temuan.checklist.store', ['periode' => $periode]), [
                'lokasi_id' => $lokasi->id,
                'nama_checklist' => 'Checklist Lab Biologi',
            ])
            ->assertOk();

        $this->assertDatabaseHas('master_checklist', [
            'lokasi_id' => $lokasi->id,
            'nama_checklist' => 'Checklist Lab Biologi',
            'jenis_pengelola' => 'Kalab',
            'status' => 'Aktif',
        ]);
    }

    public function test_petugas_dapat_tambah_item_pada_checklist_laboratorium_dari_riwayat_patroli(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create([
            'nama_lokasi' => 'Lab Fisika',
            'jenis_lokasi' => 'Laboratorium',
        ]);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Lab Fisika',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Kalab',
            'status' => 'Aktif',
        ]);
        $periode = PatroliPeriode::keyFromDate(now());

        $overview = app(\App\Services\Patroli\PatroliRiwayatOverviewService::class)
            ->overview($petugas, $periode);

        $checklistIds = collect($overview['temuan']['checklist_options'])->pluck('id')->all();
        $this->assertContains($checklist->id, $checklistIds);

        $this->actingAs($petugas)
            ->postJson(route('patroli.riwayat.temuan.items.store', [
                'periode' => $periode,
                'masterChecklist' => $checklist->id,
            ]), [
                'nama_item' => 'Alat lab tidak rapi',
                'probability' => 2,
                'severity' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.nama_item', 'Alat lab tidak rapi');

        $this->assertDatabaseHas('item_checklist', [
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Alat lab tidak rapi',
            'status' => 'Aktif',
        ]);
    }

    public function test_petugas_dapat_toggle_status_item_dari_riwayat_patroli(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Toggle',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $item = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Kabel terkelupas',
            'probability' => 2,
            'severity' => 3,
            'skor_risiko' => 6,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
        ]);
        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.temuan.items.toggle-status', [
                'periode' => $periode,
                'itemChecklist' => $item->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.status', 'Nonaktif');

        $this->assertDatabaseHas('item_checklist', [
            'id' => $item->id,
            'status' => 'Nonaktif',
        ]);
    }

    public function test_lokasi_belum_tetap_di_patroli_saat_semua_item_dinonaktifkan(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung', 'nama_lokasi' => 'Gedung Toggle']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Toggle',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $item = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Satu-satunya item',
            'probability' => 2,
            'severity' => 2,
            'skor_risiko' => 4,
            'level_risiko' => 'Rendah',
            'status' => 'Aktif',
        ]);
        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.temuan.items.toggle-status', [
                'periode' => $periode,
                'itemChecklist' => $item->id,
            ]))
            ->assertOk();

        $overview = app(\App\Services\Patroli\PatroliRiwayatOverviewService::class)
            ->overview($petugas, $periode);

        $row = collect($overview['temuan']['lokasi'])->firstWhere('lokasi_id', $lokasi->id);

        $this->assertSame('belum', $row['status']);
        $this->assertSame(0, $row['item_count']);
        $this->assertCount(1, $row['checklist_items']);
        $this->assertFalse($row['checklist_items'][0]['aktif']);
        $this->assertTrue($row['checklist_live']);
    }

    public function test_item_baru_tidak_muncul_di_snapshot_inspeksi_periode_lama(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung', 'nama_lokasi' => 'Gedung Snapshot']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Snapshot',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $itemLama = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Item lama',
            'probability' => 2,
            'severity' => 2,
            'skor_risiko' => 4,
            'level_risiko' => 'Rendah',
            'status' => 'Aktif',
            'urutan' => 1,
        ]);

        $this->actingAs($petugas)->postJson(route('patroli.inspeksi.store'), [
            'sections' => [[
                'lokasi_id' => $lokasi->id,
                'master_checklist_id' => $checklist->id,
                'items' => [['item_checklist_id' => $itemLama->id, 'status' => 'ya']],
            ]],
        ])->assertOk();

        $periode = PatroliPeriode::keyFromDate(now());

        ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Item baru setelah inspeksi',
            'probability' => 3,
            'severity' => 3,
            'skor_risiko' => 9,
            'level_risiko' => 'Sedang',
            'status' => 'Aktif',
            'urutan' => 2,
        ]);

        $overview = app(\App\Services\Patroli\PatroliRiwayatOverviewService::class)
            ->overview($petugas, $periode);

        $row = collect($overview['temuan']['lokasi'])->firstWhere('lokasi_id', $lokasi->id);

        $this->assertSame('selesai', $row['status']);
        $this->assertCount(1, $row['checklist_items']);
        $this->assertSame('Item lama', $row['checklist_items'][0]['nama_item']);
        $this->assertFalse($row['checklist_live']);
    }

    public function test_periode_selesai_tidak_menampilkan_item_checklist_terbaru_untuk_lokasi_belum(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung', 'nama_lokasi' => 'Gedung Locked']);
        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Locked',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Item setelah periode',
            'probability' => 2,
            'severity' => 2,
            'skor_risiko' => 4,
            'level_risiko' => 'Rendah',
            'status' => 'Aktif',
        ]);

        $periode = PatroliPeriode::keyFromDate(now());
        $parsed = PatroliPeriode::parse($periode);

        PatroliLaporanPeriode::query()->create([
            'petugas_id' => $petugas->id,
            'tahun' => $parsed['year'],
            'caturwulan' => $parsed['caturwulan'],
            'jenis' => PatroliLaporanPeriode::JENIS_TEMUAN,
            'status' => PatroliLaporanPeriode::STATUS_SELESAI,
            'selesai_at' => now(),
        ]);

        $overview = app(\App\Services\Patroli\PatroliRiwayatOverviewService::class)
            ->overview($petugas, $periode);

        $row = collect($overview['temuan']['lokasi'])->firstWhere('lokasi_id', $lokasi->id);

        $this->assertSame('belum', $row['status']);
        $this->assertSame([], $row['checklist_items']);
        $this->assertFalse($row['checklist_live']);
        $this->assertFalse($overview['temuan']['can_modify']);
    }

    public function test_periode_historis_tidak_menampilkan_inventaris_yang_belum_ada_di_periode_tersebut(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $periodeLama = '2026-1';
        $akhirPeriodeLama = PatroliPeriode::dateRangeForKey($periodeLama)[1];

        $lokasiLama = Lokasi::factory()->create([
            'nama_lokasi' => 'Gedung CW I',
            'jenis_lokasi' => 'Gedung',
        ]);
        $lokasiLama->forceFill(['created_at' => $akhirPeriodeLama->copy()->subMonth()])->save();

        $checklistLama = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist CW I',
            'lokasi_id' => $lokasiLama->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        $checklistLama->forceFill(['created_at' => $akhirPeriodeLama->copy()->subMonth()])->save();

        $itemLama = ItemChecklist::query()->create([
            'master_checklist_id' => $checklistLama->id,
            'nama_item' => 'Item CW I',
            'probability' => 2,
            'severity' => 2,
            'skor_risiko' => 4,
            'level_risiko' => 'Rendah',
            'status' => 'Aktif',
            'urutan' => 1,
        ]);
        $itemLama->forceFill(['created_at' => $akhirPeriodeLama->copy()->subMonth()])->save();

        $aparLama = Apar::factory()->create(['lokasi_id' => $lokasiLama->id]);
        $aparLama->forceFill(['created_at' => $akhirPeriodeLama->copy()->subMonth()])->save();

        $lokasiBaru = Lokasi::factory()->create([
            'nama_lokasi' => 'Gedung CW II',
            'jenis_lokasi' => 'Gedung',
        ]);
        $checklistBaru = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist CW II',
            'lokasi_id' => $lokasiBaru->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);
        ItemChecklist::query()->create([
            'master_checklist_id' => $checklistBaru->id,
            'nama_item' => 'Item CW II',
            'probability' => 2,
            'severity' => 2,
            'skor_risiko' => 4,
            'level_risiko' => 'Rendah',
            'status' => 'Aktif',
            'urutan' => 1,
        ]);
        $aparBaru = Apar::factory()->create(['lokasi_id' => $lokasiBaru->id]);

        $checklistBaruDiLokasiLama = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist baru di lokasi lama',
            'lokasi_id' => $lokasiLama->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $overview = app(\App\Services\Patroli\PatroliRiwayatOverviewService::class)
            ->overview($petugas, $periodeLama);

        $lokasiIds = collect($overview['temuan']['lokasi'])->pluck('lokasi_id')->all();
        $this->assertContains($lokasiLama->id, $lokasiIds);
        $this->assertNotContains($lokasiBaru->id, $lokasiIds);

        $rowLama = collect($overview['temuan']['lokasi'])->firstWhere('lokasi_id', $lokasiLama->id);
        $this->assertSame('Checklist CW I', $rowLama['nama_checklist']);
        $this->assertCount(1, $rowLama['checklist_items']);
        $this->assertSame('Item CW I', $rowLama['checklist_items'][0]['nama_item']);

        $aparIds = collect($overview['apar']['units'])->pluck('apar_id')->all();
        $this->assertContains($aparLama->id, $aparIds);
        $this->assertNotContains($aparBaru->id, $aparIds);

        $this->assertDatabaseHas('master_checklist', ['id' => $checklistBaruDiLokasiLama->id]);
        $this->assertSame('Checklist CW I', $rowLama['nama_checklist']);

        Carbon::setTestNow();
    }

    public function test_tidak_bisa_selesai_temuan_jika_masih_ada_lokasi_belum_dicek(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasiDicek = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung', 'nama_lokasi' => 'Gedung Dicek']);
        $lokasiBelum = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung', 'nama_lokasi' => 'Gedung Belum']);

        foreach ([$lokasiDicek, $lokasiBelum] as $lokasi) {
            $checklist = MasterChecklist::query()->create([
                'nama_checklist' => 'Checklist '.$lokasi->nama_lokasi,
                'lokasi_id' => $lokasi->id,
                'dibuat_oleh_id' => $petugas->id,
                'jenis_pengelola' => 'Petugas K3LH',
                'status' => 'Aktif',
            ]);
            ItemChecklist::query()->create([
                'master_checklist_id' => $checklist->id,
                'nama_item' => 'Item '.$lokasi->nama_lokasi,
                'probability' => 2,
                'severity' => 2,
                'skor_risiko' => 4,
                'level_risiko' => 'Rendah',
                'status' => 'Aktif',
            ]);
        }

        $checklistDicek = MasterChecklist::query()->where('lokasi_id', $lokasiDicek->id)->firstOrFail();
        $itemDicek = ItemChecklist::query()->where('master_checklist_id', $checklistDicek->id)->firstOrFail();

        $this->actingAs($petugas)->postJson(route('patroli.inspeksi.store'), [
            'sections' => [[
                'lokasi_id' => $lokasiDicek->id,
                'master_checklist_id' => $checklistDicek->id,
                'items' => [['item_checklist_id' => $itemDicek->id, 'status' => 'ya']],
            ]],
        ])->assertOk();

        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.temuan.selesai', ['periode' => $periode]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['periode']);
    }

    public function test_tidak_bisa_selesai_apar_jika_masih_ada_unit_belum_dicek(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['jenis_lokasi' => 'Gedung']);
        $aparDicek = Apar::factory()->forLokasi($lokasi)->create(['kode_apar' => 'APR-DICEK']);
        Apar::factory()->forLokasi($lokasi)->create(['kode_apar' => 'APR-BELUM']);

        $this->actingAs($petugas)
            ->post(route('patroli.apar.store'), [
                'pemeriksaan' => [[
                    'apar_id' => $aparDicek->id,
                    'kondisi_tabung' => 'Baik',
                    'kondisi_segel' => 'tersegel',
                ]],
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $periode = PatroliPeriode::keyFromDate(now());

        $this->actingAs($petugas)
            ->patchJson(route('patroli.riwayat.apar.selesai', ['periode' => $periode]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['periode']);
    }
}
