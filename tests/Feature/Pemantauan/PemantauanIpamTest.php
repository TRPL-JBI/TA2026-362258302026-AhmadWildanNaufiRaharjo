<?php

namespace Tests\Feature\Pemantauan;

use App\Models\DetailIpamMingguan;
use App\Models\LaporanIpam;
use App\Models\TitikIpam;
use App\Models\UnitIpam;
use App\Models\User;
use App\Support\IpamBulan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemantauanIpamTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_view_ipam_page(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)
            ->get(route('pemantauan.ipam'))
            ->assertOk()
            ->assertSee('Pemantauan IPAM');
    }

    public function test_petugas_can_create_laporan_ipam(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        [$unit, $titik] = $this->seedUnitWithTitik();

        $payload = $this->samplePayload($unit->id, $titik->id);

        $response = $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $payload);

        $response->assertOk()
            ->assertJsonPath('listItem.bulan', 'Januari')
            ->assertJsonPath('listItem.status', IpamBulan::STATUS_BERLANGSUNG);

        $this->assertSame(1, LaporanIpam::query()->count());
        $this->assertSame(1, DetailIpamMingguan::query()->count());

        $laporan = LaporanIpam::query()->first();
        $this->assertSame($user->id, $laporan->petugas_id);
        $this->assertSame('Kesimpulan uji', $laporan->kesimpulan);
    }

    public function test_can_save_when_only_some_titik_are_filled(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $unit = UnitIpam::factory()->create();
        $titik1 = TitikIpam::factory()->create(['unit_ipam_id' => $unit->id, 'titik_lokasi' => 'Titik A']);
        $titik2 = TitikIpam::factory()->create(['unit_ipam_id' => $unit->id, 'titik_lokasi' => 'Titik B']);

        $payload = $this->samplePayload($unit->id, $titik1->id);
        $payload['units'][0]['minggu_list'][0]['data_titik'] = [
            [
                'titik_id' => $titik1->id,
                'ph' => 7.2,
                'alt' => '5,50 x 10²',
                'salmonella' => 'Negatif',
                'status' => 'Baik',
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $payload)
            ->assertOk();

        $this->assertSame(1, LaporanIpam::query()->count());
        $this->assertSame(1, DetailIpamMingguan::query()->count());
        $this->assertSame($titik1->id, LaporanIpam::query()->value('titik_ipam_id'));
    }

    public function test_invalid_alt_format_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        [$unit, $titik] = $this->seedUnitWithTitik();

        $payload = $this->samplePayload($unit->id, $titik->id);
        $payload['units'][0]['minggu_list'][0]['data_titik'][0]['alt'] = 'salah format';

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['units.0.minggu_list.0.data_titik.0.alt']);
    }

    public function test_duplicate_bulan_tahun_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        [$unit, $titik] = $this->seedUnitWithTitik();

        LaporanIpam::factory()->create([
            'titik_ipam_id' => $titik->id,
            'petugas_id' => $user->id,
            'bulan' => 1,
            'tahun' => (int) date('Y'),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $this->samplePayload($unit->id, $titik->id));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['bulan']);
    }

    public function test_petugas_can_load_and_update_laporan(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        [$unit, $titik] = $this->seedUnitWithTitik();
        $tahun = (int) date('Y');
        $periodeKey = IpamBulan::periodeKey($tahun, 1);

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $this->samplePayload($unit->id, $titik->id))
            ->assertOk();

        $this->actingAs($user)
            ->getJson(route('pemantauan.ipam.show', ['periodeKey' => $periodeKey]))
            ->assertOk()
            ->assertJsonPath('data.bulan', 'Januari');

        $payload = $this->samplePayload($unit->id, $titik->id);
        $payload['notes']['kesimpulan'] = 'Kesimpulan diperbarui';

        $this->actingAs($user)
            ->putJson(route('pemantauan.ipam.update', ['periodeKey' => $periodeKey]), $payload)
            ->assertOk()
            ->assertJsonPath('data.notes.kesimpulan', 'Kesimpulan diperbarui');
    }

    public function test_petugas_can_delete_laporan_ipam(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        [$unit, $titik] = $this->seedUnitWithTitik();
        $tahun = (int) date('Y');
        $periodeKey = IpamBulan::periodeKey($tahun, 1);

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $this->samplePayload($unit->id, $titik->id))
            ->assertOk();

        $this->actingAs($user)
            ->deleteJson(route('pemantauan.ipam.destroy', ['periodeKey' => $periodeKey]))
            ->assertOk()
            ->assertJsonPath('message', 'Laporan pemantauan IPAM berhasil dihapus.');

        $this->assertSame(0, LaporanIpam::query()->count());
        $this->assertSame(0, DetailIpamMingguan::query()->count());
    }

    public function test_mark_laporan_selesai_generates_rekap_xlsx(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        [$unit, $titik] = $this->seedUnitWithTitik();
        $unit->update(['nama_unit' => 'IPAM 1']);
        $titik->update(['titik_lokasi' => 'Pusat (belakang kantin)']);
        $tahun = (int) date('Y');
        $periodeKey = IpamBulan::periodeKey($tahun, 1);

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $this->samplePayload($unit->id, $titik->id))
            ->assertOk();

        $this->actingAs($user)
            ->patchJson(route('pemantauan.ipam.selesai', ['periodeKey' => $periodeKey]))
            ->assertOk()
            ->assertJsonPath('listItem.status', IpamBulan::STATUS_SELESAI);

        $laporan = \App\Models\LaporanGenerated::query()
            ->where('jenis_laporan', \App\Models\LaporanGenerated::JENIS_IPAM)
            ->first();

        $this->assertNotNull($laporan);
        $this->assertNotNull($laporan->file_path_xlsx);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($laporan->file_path_xlsx);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
            \Illuminate\Support\Facades\Storage::disk('local')->path($laporan->file_path_xlsx),
        );
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertStringContainsString(
            'TABEL REKAP PER IPAM BULAN JANUARI',
            (string) $sheet->getCell('A2')->getCalculatedValue(),
        );
        $this->assertSame('IPAM 1', $sheet->getCell('A5')->getCalculatedValue());
        $this->assertSame('A', $sheet->getCell('C5')->getCalculatedValue());
        $this->assertSame('Pusat (belakang kantin)', $sheet->getCell('D5')->getCalculatedValue());
    }

    public function test_petugas_can_mark_laporan_selesai(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        [$unit, $titik] = $this->seedUnitWithTitik();
        $tahun = (int) date('Y');
        $periodeKey = IpamBulan::periodeKey($tahun, 1);

        $this->actingAs($user)
            ->postJson(route('pemantauan.ipam.store'), $this->samplePayload($unit->id, $titik->id))
            ->assertOk();

        $this->actingAs($user)
            ->patchJson(route('pemantauan.ipam.selesai', ['periodeKey' => $periodeKey]))
            ->assertOk()
            ->assertJsonPath('listItem.status', IpamBulan::STATUS_SELESAI);

        $this->assertSame(
            IpamBulan::STATUS_SELESAI,
            LaporanIpam::query()->value('status'),
        );
    }

    /**
     * @return array{0: UnitIpam, 1: TitikIpam}
     */
    private function seedUnitWithTitik(): array
    {
        $unit = UnitIpam::factory()->create();
        $titik = TitikIpam::factory()->create(['unit_ipam_id' => $unit->id]);

        return [$unit, $titik];
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePayload(int $unitId, int $titikId): array
    {
        return [
            'bulan' => 'Januari',
            'tahun' => (int) date('Y'),
            'units' => [
                [
                    'unit_id' => $unitId,
                    'minggu_list' => [
                        [
                            'minggu_ke' => 1,
                            'data_titik' => [
                                [
                                    'titik_id' => $titikId,
                                    'ph' => 7.2,
                                    'alt' => '5,50 x 10²',
                                    'salmonella' => 'Negatif',
                                    'status' => 'Baik',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'notes' => [
                'kendala' => 'Tidak ada',
                'rekomendasi' => 'Lanjutkan pemantauan',
                'kesimpulan' => 'Kesimpulan uji',
            ],
        ];
    }
}
