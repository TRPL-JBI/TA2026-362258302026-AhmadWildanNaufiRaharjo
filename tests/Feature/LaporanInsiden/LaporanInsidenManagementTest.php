<?php

namespace Tests\Feature\LaporanInsiden;

use App\Models\LaporanInsiden;
use App\Models\Lokasi;
use App\Models\Notifikasi;
use App\Models\User;
use App\Notifications\WebPushAlertNotification;
use App\Support\LaporanInsidenJenis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaporanInsidenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_satpam_can_view_laporan_insiden_page(): void
    {
        $satpam = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($satpam)
            ->get(route('laporan-insiden'))
            ->assertOk()
            ->assertSee('Laporan Insiden Darurat');
    }

    public function test_satpam_can_submit_laporan_with_master_lokasi(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $satpam = User::factory()->create(['role' => 'Satpam']);
        User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Gedung A']);

        $response = $this->actingAs($satpam)
            ->post(route('laporan-insiden.store'), [
                'jenis_insiden' => LaporanInsidenJenis::KECELAKAAN_KERJA,
                'lokasi_id' => $lokasi->id,
                'tanggal' => '2026-05-20',
                'waktu' => '14:30',
                'kronologi' => 'Terjadi percikan api di ruang server lantai 2.',
                'korban_list' => [
                    [
                        'nama' => 'Budi Santoso',
                        'usia' => '21',
                        'unit_prodi' => 'AGB',
                        'jabatan' => 'Mahasiswa',
                        'status' => 'Luka Ringan',
                    ],
                    [
                        'nama' => 'Siti Aminah',
                        'usia' => '19',
                        'unit_prodi' => 'TI',
                        'jabatan' => 'Mahasiswa',
                        'status' => 'Luka Berat',
                    ],
                ],
                'foto' => [
                    UploadedFile::fake()->image('tkp1.jpg', 800, 600),
                    UploadedFile::fake()->image('tkp2.jpg', 800, 600),
                ],
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.nomor', 'INS-00001');

        $laporan = LaporanInsiden::query()->first();
        $this->assertNotNull($laporan);
        $this->assertSame($satpam->id, $laporan->satpam_id);
        $this->assertSame($lokasi->id, $laporan->lokasi_id);
        $this->assertNull($laporan->lokasi_manual);
        $this->assertSame(LaporanInsidenJenis::KECELAKAAN_KERJA, $laporan->jenis_insiden);
        $this->assertStringContainsString('Budi Santoso', (string) $laporan->korban);
        $this->assertStringContainsString('Siti Aminah', (string) $laporan->korban);
        $this->assertSame('21', $laporan->usia_korban);
        $this->assertSame('AGB', $laporan->unit_prodi);
        $this->assertSame('Mahasiswa', $laporan->jabatan_korban);
        $this->assertSame('Luka Ringan', $laporan->status_korban);
        $this->assertNotNull($laporan->foto_path);

        $this->assertDatabaseHas('tindak_lanjut_insiden', [
            'laporan_insiden_id' => $laporan->id,
            'status_perbaikan' => 'Dalam Proses',
        ]);

        $this->assertSame(1, Notifikasi::query()->where('jenis_notifikasi', 'Laporan Insiden')->count());
        $this->assertSame($laporan->id, Notifikasi::query()->value('reference_id'));

        $generated = \App\Models\LaporanGenerated::query()
            ->where('jenis_laporan', \App\Models\LaporanGenerated::JENIS_INSIDEN)
            ->first();
        $this->assertNotNull($generated);
        $this->assertNotNull($generated->file_path_docx);
        $this->assertNull($generated->file_path_xlsx);
        Storage::disk('local')->assertExists($generated->file_path_docx);

        $docxPath = Storage::disk('local')->path($generated->file_path_docx);
        $this->assertFileExists($docxPath);
        $this->assertGreaterThan(1000, filesize($docxPath));

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true);
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($documentXml);
        $this->assertStringContainsString('KECELAKAAN KERJA', $documentXml);
        $this->assertStringContainsString('Budi Santoso', $documentXml);
        $this->assertStringContainsString('Siti Aminah', $documentXml);
        $this->assertStringContainsString('21', $documentXml);
        $this->assertStringContainsString('Gedung A', $documentXml);
        $this->assertStringContainsString('Luka Ringan', $documentXml);
        $this->assertStringContainsString('Luka Berat', $documentXml);
        $this->assertStringContainsString('Kronologi', $documentXml);
        $this->assertStringContainsString('Terjadi percikan api di ruang server lantai 2.', $documentXml);
        $this->assertStringNotContainsString('sebelum praktikum diwajibkan safety induction', $documentXml);
        $this->assertStringContainsString('Dibuat', $documentXml);
        $this->assertStringContainsString('Diperiksa', $documentXml);
        $this->assertStringContainsString('Disetujui', $documentXml);
    }

    public function test_submit_laporan_sends_web_push_to_petugas_with_subscription(): void
    {
        Storage::fake('public');
        Notification::fake();

        config([
            'webpush.vapid.public_key' => 'test-public-key',
            'webpush.vapid.private_key' => 'test-private-key',
        ]);

        $satpam = User::factory()->create(['role' => 'Satpam']);
        $petugas = User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Gedung B']);

        $petugas->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/test-endpoint',
            str_repeat('a', 87),
            str_repeat('b', 22),
            'aesgcm',
        );

        $this->actingAs($satpam)
            ->post(route('laporan-insiden.store'), [
                'jenis_insiden' => LaporanInsidenJenis::KEBAKARAN,
                'lokasi_id' => $lokasi->id,
                'tanggal' => '2026-05-20',
                'waktu' => '14:30',
                'kronologi' => 'Terjadi percikan api di ruang server lantai 2.',
                'foto' => [UploadedFile::fake()->image('tkp.jpg')],
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        Notification::assertSentTo($petugas, WebPushAlertNotification::class);
    }

    public function test_satpam_can_submit_laporan_with_manual_lokasi(): void
    {
        Storage::fake('public');

        $satpam = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($satpam)
            ->post(route('laporan-insiden.store'), [
                'jenis_insiden' => LaporanInsidenJenis::GANGGUAN_KEAMANAN,
                'lokasi_manual' => 'Parkir motor sisi barat',
                'tanggal' => '2026-05-21',
                'waktu' => '09:15',
                'kronologi' => 'Terjadi keributan antar pengunjung di area parkir.',
                'foto' => [UploadedFile::fake()->image('tkp.jpg')],
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->assertDatabaseHas('laporan_insiden', [
            'satpam_id' => $satpam->id,
            'lokasi_id' => null,
            'lokasi_manual' => 'Parkir motor sisi barat',
        ]);
    }

    public function test_petugas_cannot_submit_laporan_insiden(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();

        $this->actingAs($petugas)
            ->postJson(route('laporan-insiden.store'), [
                'jenis_insiden' => LaporanInsidenJenis::KEBAKARAN,
                'lokasi_id' => $lokasi->id,
                'tanggal' => '2026-05-20',
                'waktu' => '10:00',
                'kronologi' => 'Upaya submit oleh role yang salah.',
                'foto' => [UploadedFile::fake()->image('x.jpg')],
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_submit_requires_foto_and_lokasi(): void
    {
        $satpam = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($satpam)
            ->postJson(route('laporan-insiden.store'), [
                'jenis_insiden' => LaporanInsidenJenis::BENCANA_ALAM,
                'tanggal' => '2026-05-20',
                'waktu' => '08:00',
                'kronologi' => 'Kronologi valid minimal sepuluh.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto', 'lokasi_id']);
    }

    public function test_petugas_cannot_access_laporan_insiden_page(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($petugas)
            ->get(route('laporan-insiden'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }
}
