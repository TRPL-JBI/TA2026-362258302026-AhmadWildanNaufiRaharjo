<?php

namespace Tests\Feature\Pemantauan;

use App\Models\DetailIpalHarian;
use App\Models\LaporanIpal;
use App\Models\User;
use App\Support\IpalTriwulan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemantauanIpalTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_view_ipal_page(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->get(route('pemantauan.ipal'))
            ->assertOk()
            ->assertSee('Pemantauan IPAL');
    }

    public function test_petugas_can_create_laporan_ipal(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $payload = $this->samplePayload();

        $response = $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), $payload);

        $response->assertOk()
            ->assertJsonPath('listItem.triwulanKey', 'Triwulan I (Jan - Mar)');

        $laporan = LaporanIpal::query()->first();
        $this->assertNotNull($laporan);
        $this->assertSame($user->id, $laporan->petugas_id);
        $this->assertSame(1, $laporan->triwulan);
        $this->assertSame((int) date('Y'), $laporan->tahun);
        $this->assertSame(IpalTriwulan::STATUS_BERLANGSUNG, $laporan->status);

        $this->assertSame(3, DetailIpalHarian::query()->where('laporan_ipal_id', $laporan->id)->count());
        $this->assertDatabaseHas('dampak_lingkungan_ipal', [
            'laporan_ipal_id' => $laporan->id,
            'jenis_dampak' => 'Penurunan kualitas air',
        ]);
    }

    public function test_duplicate_triwulan_tahun_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        LaporanIpal::factory()->create([
            'petugas_id' => $user->id,
            'triwulan' => 1,
            'tahun' => (int) date('Y'),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), $this->samplePayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['triwulan_key']);
    }

    public function test_petugas_can_delete_laporan_ipal(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanIpal::query()->firstOrFail();

        $this->actingAs($user)
            ->deleteJson(route('pemantauan.ipal.destroy', $laporan))
            ->assertOk()
            ->assertJsonPath('message', 'Laporan pemantauan IPAL berhasil dihapus.');

        $this->assertSame(0, LaporanIpal::query()->count());
        $this->assertSame(0, DetailIpalHarian::query()->count());
        $this->assertDatabaseMissing('dampak_lingkungan_ipal', [
            'laporan_ipal_id' => $laporan->id,
        ]);
    }

    public function test_mark_laporan_selesai_generates_swapantau_docx(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), $this->samplePayload())
            ->assertOk();

        $laporan = LaporanIpal::query()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('pemantauan.ipal.selesai', $laporan))
            ->assertOk()
            ->assertJsonPath('listItem.status', IpalTriwulan::STATUS_SELESAI);

        $generated = \App\Models\LaporanGenerated::query()
            ->where('jenis_laporan', \App\Models\LaporanGenerated::JENIS_IPAL)
            ->first();

        $this->assertNotNull($generated);
        $this->assertNotNull($generated->file_path_docx);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($generated->file_path_docx);

        $docxPath = \Illuminate\Support\Facades\Storage::disk('local')->path($generated->file_path_docx);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true);

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('LAPORAN PEMANTAUAN AIR LIMBAH', $documentXml);
        $this->assertStringContainsString('A. IDENTITAS PERUSAHAAN', $documentXml);
        $this->assertStringContainsString('B. LOKASI USAHA DAN ATAU KEGIATAN', $documentXml);
        $this->assertStringContainsString('Catatan Pemantauan Air Limbah Harian', $documentXml);
        $this->assertStringContainsString('Tabel Pengelolaan dan Pemantauan Air Limbah', $documentXml);
        $this->assertStringContainsString('LAMPIRKAN SEMUA HASIL UJI LAB SETIAP BULANNYA', $documentXml);
        $this->assertStringContainsString('Penurunan kualitas air', $documentXml);
    }

    public function test_petugas_can_mark_laporan_selesai(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $laporan = LaporanIpal::factory()->create([
            'petugas_id' => $user->id,
            'triwulan' => 2,
            'tahun' => (int) date('Y'),
        ]);

        $this->actingAs($user)
            ->patchJson(route('pemantauan.ipal.selesai', $laporan))
            ->assertOk()
            ->assertJsonPath('listItem.status', IpalTriwulan::STATUS_SELESAI);

        $this->assertSame(
            IpalTriwulan::STATUS_SELESAI,
            $laporan->fresh()->status,
        );
    }

    public function test_edit_data_available_after_mark_selesai(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $tahun = (int) date('Y');

        $this->actingAs($user)->postJson(route('pemantauan.ipal.store'), [
            'triwulan_key' => 'Triwulan II (Apr - Jun)',
            'tahun' => $tahun,
            'bulan_list' => [
                [
                    'nama' => 'April',
                    'catatan' => [
                        [
                            'tanggal' => "{$tahun}-04-10",
                            'debit_in' => 10,
                            'debit_out' => 9,
                            'ph' => 7.2,
                            'suhu' => 29,
                        ],
                    ],
                ],
            ],
            'evaluasi' => [
                'jenis_dampak' => 'Dampak uji',
                'sumber_dampak' => 'IPAL',
                'parameter_pemantauan' => 'pH',
                'tolak_ukur' => 'PermenLH',
            ],
        ])->assertOk();

        $laporan = LaporanIpal::query()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('pemantauan.ipal.selesai', $laporan))
            ->assertOk();

        $this->actingAs($user)
            ->getJson(route('pemantauan.ipal.show', $laporan))
            ->assertOk()
            ->assertJsonPath('data.status', IpalTriwulan::STATUS_SELESAI)
            ->assertJsonPath('data.bulanList.0.nama', 'April')
            ->assertJsonPath('data.bulanList.0.catatan.0.pH', '7.20')
            ->assertJsonPath('data.evaluasi.jenisDampak', 'Dampak uji');
    }

    public function test_kalab_cannot_access_ipal_routes(): void
    {
        $user = User::factory()->create(['role' => 'Kalab']);

        $this->actingAs($user)
            ->get(route('pemantauan.ipal'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_petugas_can_create_laporan_with_single_month_catatan(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $tahun = (int) date('Y');

        $payload = [
            'triwulan_key' => 'Triwulan I (Jan - Mar)',
            'tahun' => $tahun,
            'bulan_list' => [
                [
                    'nama' => 'Januari',
                    'catatan' => [
                        [
                            'tanggal' => "{$tahun}-01-15",
                            'debit_in' => 12.5,
                            'debit_out' => 11.2,
                            'ph' => 7.1,
                            'suhu' => 28.5,
                        ],
                    ],
                ],
            ],
            'evaluasi' => [],
        ];

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), $payload)
            ->assertOk();

        $this->assertSame(1, DetailIpalHarian::query()->count());
    }

    public function test_store_rejects_ph_outside_valid_range(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $tahun = (int) date('Y');

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), [
                'triwulan_key' => 'Triwulan III (Jul - Sep)',
                'tahun' => $tahun,
                'bulan_list' => [
                    [
                        'nama' => 'Juli',
                        'catatan' => [
                            [
                                'tanggal' => "{$tahun}-07-10",
                                'debit_in' => 10,
                                'debit_out' => 9,
                                'ph' => 15,
                                'suhu' => 28,
                            ],
                        ],
                    ],
                ],
                'evaluasi' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bulan_list.0.catatan.0.ph']);
    }

    public function test_store_accepts_comma_decimal_ph(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $tahun = (int) date('Y');

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), [
                'triwulan_key' => 'Triwulan IV (Okt - Des)',
                'tahun' => $tahun,
                'bulan_list' => [
                    [
                        'nama' => 'Oktober',
                        'catatan' => [
                            [
                                'tanggal' => "{$tahun}-10-10",
                                'debit_in' => 10,
                                'debit_out' => 9,
                                'ph' => '7,5',
                                'suhu' => '28,5',
                            ],
                        ],
                    ],
                ],
                'evaluasi' => [],
            ])
            ->assertOk();

        $this->assertDatabaseHas('detail_ipal_harian', [
            'ph' => 7.5,
            'suhu_celcius' => 28.5,
        ]);
    }

    public function test_store_rejects_empty_catatan_list(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipal.store'), [
                'triwulan_key' => 'Triwulan II (Apr - Jun)',
                'tahun' => (int) date('Y'),
                'bulan_list' => [],
                'evaluasi' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bulan_list']);
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePayload(): array
    {
        $tahun = (int) date('Y');

        return [
            'triwulan_key' => 'Triwulan I (Jan - Mar)',
            'tahun' => $tahun,
            'bulan_list' => [
                [
                    'nama' => 'Januari',
                    'catatan' => [
                        [
                            'tanggal' => "{$tahun}-01-15",
                            'debit_in' => 12.5,
                            'debit_out' => 11.2,
                            'ph' => 7.1,
                            'suhu' => 28.5,
                        ],
                    ],
                ],
                [
                    'nama' => 'Februari',
                    'catatan' => [
                        [
                            'tanggal' => "{$tahun}-02-10",
                            'debit_in' => 10,
                            'debit_out' => 9.5,
                            'ph' => 7,
                            'suhu' => 29,
                        ],
                    ],
                ],
                [
                    'nama' => 'Maret',
                    'catatan' => [
                        [
                            'tanggal' => "{$tahun}-03-05",
                            'debit_in' => 11,
                            'debit_out' => 10,
                            'ph' => 6.9,
                            'suhu' => 30,
                        ],
                    ],
                ],
            ],
            'evaluasi' => [
                'jenis_dampak' => 'Penurunan kualitas air',
                'sumber_dampak' => 'Operasional IPAL',
                'parameter_pemantauan' => 'pH, BOD, COD',
                'tolak_ukur' => 'PermenLH',
                'evaluasi_hasil' => 'Memenuhi baku mutu',
            ],
        ];
    }
}
