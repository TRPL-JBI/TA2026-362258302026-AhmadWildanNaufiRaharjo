<?php

namespace Tests\Feature\Inventaris;

use App\Models\Lokasi;
use App\Models\User;
use App\Services\LokasiQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LokasiManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_petugas_can_create_lokasi_with_auto_qr_code(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $response = $this->actingAs($user)->post(route('inventaris.lokasi.store'), [
            'nama_lokasi' => 'Gedung Teknik Sipil',
            'jenis_lokasi' => 'Gedung',
            'deskripsi' => 'Area utama',
        ]);

        $response->assertRedirect(route('inventaris.lokasi'));
        $response->assertSessionHas('success');

        $lokasi = Lokasi::query()->first();
        $this->assertNotNull($lokasi);
        $this->assertSame('GED-001', $lokasi->kode_lokasi);
        $this->assertNotNull($lokasi->qr_code_path);
        Storage::disk('public')->assertExists($lokasi->qr_code_path);

        $decoded = json_decode(app(LokasiQrCodeService::class)->content($lokasi), true);
        $this->assertSame('lokasi', $decoded['type']);
        $this->assertSame($lokasi->id, $decoded['id']);
        $this->assertSame('GED-001', $decoded['kode']);
    }

    public function test_petugas_can_view_and_print_qr_pages(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create([
            'kode_lokasi' => 'LAB-001',
            'nama_lokasi' => 'Lab Kimia',
            'jenis_lokasi' => 'Laboratorium',
        ]);

        $path = app(LokasiQrCodeService::class)->generate($lokasi);
        $lokasi->update(['qr_code_path' => $path]);

        $this->actingAs($user)
            ->get(route('inventaris.lokasi.qr.image', $lokasi))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');

        $this->actingAs($user)
            ->get(route('inventaris.lokasi.qr.print', $lokasi))
            ->assertOk()
            ->assertSee('Cetak')
            ->assertSee('Lab Kimia')
            ->assertDontSee('LAB-001');
    }

    public function test_petugas_can_print_multiple_qr_codes_in_one_page(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $service = app(LokasiQrCodeService::class);

        $items = collect([
            ['kode_lokasi' => 'GED-001', 'nama_lokasi' => 'Gedung A', 'jenis_lokasi' => 'Gedung'],
            ['kode_lokasi' => 'LAB-001', 'nama_lokasi' => 'Lab Kimia', 'jenis_lokasi' => 'Laboratorium'],
            ['kode_lokasi' => 'RU-001', 'nama_lokasi' => 'Ruang Server', 'jenis_lokasi' => 'Ruangan'],
            ['kode_lokasi' => 'GED-002', 'nama_lokasi' => 'Gedung B', 'jenis_lokasi' => 'Gedung'],
        ])->map(function (array $data) use ($service) {
            $lokasi = Lokasi::factory()->create($data);
            $lokasi->update(['qr_code_path' => $service->generate($lokasi)]);

            return $lokasi;
        });

        $ids = $items->pluck('id')->implode(',');

        $this->actingAs($user)
            ->get(route('inventaris.lokasi.qr.print-batch', ['ids' => $ids]))
            ->assertOk()
            ->assertSee('Gedung A')
            ->assertSee('Lab Kimia')
            ->assertSee('Ruang Server')
            ->assertSee('Gedung B')
            ->assertSee('4 QR Code');
    }

    public function test_petugas_can_update_lokasi(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);
        $lokasi = Lokasi::factory()->create([
            'kode_lokasi' => 'RU-001',
            'nama_lokasi' => 'Ruang Lama',
            'jenis_lokasi' => 'Ruangan',
        ]);

        $this->actingAs($user)->put(route('inventaris.lokasi.update', $lokasi), [
            'nama_lokasi' => 'Ruang Baru',
            'jenis_lokasi' => 'Ruangan',
            'deskripsi' => null,
        ])->assertRedirect(route('inventaris.lokasi'));

        $lokasi->refresh();
        $this->assertSame('Ruang Baru', $lokasi->nama_lokasi);
    }

    public function test_satpam_cannot_store_lokasi(): void
    {
        $user = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($user)->post(route('inventaris.lokasi.store'), [
            'nama_lokasi' => 'Test',
            'jenis_lokasi' => 'Gedung',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('lokasi', 0);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($user)->post(route('inventaris.lokasi.store'), [])
            ->assertSessionHasErrors(['nama_lokasi', 'jenis_lokasi']);
    }
}
