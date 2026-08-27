<?php

namespace Tests\Feature\Inventaris;

use App\Models\Apar;
use App\Models\Lokasi;
use App\Models\User;
use App\Services\AparQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AparManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_petugas_can_create_apar_with_auto_kode_and_qr(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create([
            'kode_lokasi' => 'GED-001',
            'nama_lokasi' => 'Gedung A',
            'jenis_lokasi' => 'Gedung',
        ]);

        $response = $this->actingAs($user)->post(route('inventaris.apar.store'), [
            'lokasi_id' => $lokasi->id,
            'jenis_apar' => 'Powder',
            'kapasitas_kg' => 3,
            'tanggal_expired' => now()->addYear()->format('Y-m-d'),
            'keterangan' => 'Unit baru',
        ]);

        $response->assertRedirect(route('inventaris.apar'));
        $response->assertSessionHas('success');

        $apar = Apar::query()->first();
        $this->assertNotNull($apar);
        $this->assertSame('APAR-GED-001', $apar->kode_apar);
        $this->assertNull($apar->status_kondisi);
        $this->assertNull($apar->kondisiBadge());
        $this->assertNotNull($apar->qr_code_path);
        Storage::disk('public')->assertExists($apar->qr_code_path);

        $decoded = json_decode(app(AparQrCodeService::class)->content($apar), true);
        $this->assertSame('apar', $decoded['type']);
        $this->assertSame($apar->id, $decoded['id']);
        $this->assertSame('APAR-GED-001', $decoded['kode']);
        $this->assertArrayNotHasKey('lokasi', $decoded);
        $this->assertArrayNotHasKey('kapasitas', $decoded);
    }

    public function test_petugas_can_view_and_print_qr_pages(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['kode_lokasi' => 'LAB-001']);
        $apar = Apar::factory()->forLokasi($lokasi)->create([
            'jenis_apar' => 'CO2',
            'kapasitas_kg' => 5,
        ]);

        $path = app(AparQrCodeService::class)->generate($apar);
        $apar->update(['qr_code_path' => $path]);

        $this->actingAs($user)
            ->get(route('inventaris.apar.qr.image', $apar))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');

        $this->actingAs($user)
            ->get(route('inventaris.apar.qr.print', $apar))
            ->assertOk()
            ->assertSee('Cetak')
            ->assertSee($apar->kode_apar);
    }

    public function test_petugas_can_update_apar_without_changing_kondisi(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create(['kode_lokasi' => 'RU-001']);
        $apar = Apar::factory()->forLokasi($lokasi)->create([
            'jenis_apar' => 'Powder',
            'kapasitas_kg' => 3,
            'status_kondisi' => Apar::KONDISI_BAIK_TERSEGEL,
        ]);

        $this->actingAs($user)->put(route('inventaris.apar.update', $apar), [
            'lokasi_id' => $lokasi->id,
            'jenis_apar' => 'Foam',
            'kapasitas_kg' => 6,
            'tanggal_expired' => now()->addMonths(6)->format('Y-m-d'),
            'keterangan' => null,
        ])->assertRedirect(route('inventaris.apar'));

        $apar->refresh();
        $this->assertSame('Foam', $apar->jenis_apar);
        $this->assertSame(Apar::KONDISI_BAIK_TERSEGEL, $apar->status_kondisi);
    }

    public function test_index_shows_kondisi_segel_badges(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create();

        Apar::factory()->forLokasi($lokasi)->create([
            'kode_apar' => 'APAR-TES-001',
            'status_kondisi' => Apar::KONDISI_BAIK_TERSEGEL,
        ]);
        Apar::factory()->forLokasi($lokasi)->create([
            'kode_apar' => 'APAR-TES-002',
            'status_kondisi' => Apar::KONDISI_TERBUKA,
        ]);
        Apar::factory()->forLokasi($lokasi)->create([
            'kode_apar' => 'APAR-TES-003',
            'status_kondisi' => null,
        ]);

        $this->actingAs($user)
            ->get(route('inventaris.apar'))
            ->assertOk()
            ->assertSee('Baik Tersegel')
            ->assertSee('Terbuka')
            ->assertDontSee('Belum diperiksa');
    }

    public function test_status_kondisi_maps_from_patroli_segel_values(): void
    {
        $this->assertSame(
            Apar::KONDISI_BAIK_TERSEGEL,
            Apar::statusKondisiFromSegel('tersegel'),
        );
        $this->assertSame(
            Apar::KONDISI_TERBUKA,
            Apar::statusKondisiFromSegel('tidak-tersegel'),
        );
    }

    public function test_satpam_cannot_store_apar(): void
    {
        $user = User::factory()->create(['role' => 'Satpam']);
        $lokasi = Lokasi::factory()->create();

        $this->actingAs($user)->post(route('inventaris.apar.store'), [
            'lokasi_id' => $lokasi->id,
            'jenis_apar' => 'Powder',
            'kapasitas_kg' => 3,
            'tanggal_expired' => now()->addYear()->format('Y-m-d'),
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('apar', 0);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)->post(route('inventaris.apar.store'), [])
            ->assertSessionHasErrors(['lokasi_id', 'jenis_apar', 'kapasitas_kg', 'tanggal_expired']);
    }
}
