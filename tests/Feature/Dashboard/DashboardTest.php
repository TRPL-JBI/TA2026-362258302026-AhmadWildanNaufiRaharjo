<?php

namespace Tests\Feature\Dashboard;

use App\Models\Apar;
use App\Models\DetailInspeksi;
use App\Models\InspeksiK3l;
use App\Models\ItemChecklist;
use App\Models\LaporanInsiden;
use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\TindakLanjutInsiden;
use App\Models\TindakLanjutInspeksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard_with_live_data(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $pimpinan = User::factory()->create(['role' => 'Pimpinan']);
        $petugas = User::factory()->create(['role' => 'Petugas K3LH', 'nama_lengkap' => 'Budi K3LH']);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Lab Kimia']);

        $checklist = MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Lab Kimia',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => 'Petugas K3LH',
            'status' => 'Aktif',
        ]);

        $item = ItemChecklist::query()->create([
            'master_checklist_id' => $checklist->id,
            'nama_item' => 'Kabel terkelupas',
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
            'item_checklist_id' => $item->id,
            'status' => DetailInspeksi::STATUS_TIDAK,
            'analisa_risiko' => 'Kabel tidak terlindung',
            'rekomendasi' => 'Ganti kabel',
            'skor_risiko_hasil' => 16,
            'level_risiko_hasil' => 'Sangat Tinggi',
        ]);

        TindakLanjutInspeksi::query()->create([
            'detail_inspeksi_id' => $detail->id,
            'petugas_id' => $petugas->id,
            'status_perbaikan' => 'Dalam Proses',
            'tanggal_tindakan' => now(),
            'catatan_perbaikan' => 'Sedang dikerjakan teknisi',
        ]);

        Apar::factory()->forLokasi($lokasi)->create([
            'tanggal_expired' => now()->addDays(10),
        ]);
        Apar::factory()->forLokasi($lokasi)->create([
            'tanggal_expired' => now()->subDays(5),
        ]);

        $this->actingAs($pimpinan)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Eksekutif')
            ->assertSee('1')
            ->assertSee('Lab Kimia')
            ->assertSee('Kabel tidak terlindung')
            ->assertSee('Sangat Tinggi')
            ->assertSee('Kabel terkelupas')
            ->assertSee('2 Unit')
            ->assertSee('Sudah expired atau')
            ->assertDontSee('Kepatuhan SOP');

        Carbon::setTestNow();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_risiko_chart_groups_locations_beyond_top_five(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        for ($i = 1; $i <= 7; $i++) {
            $lokasi = Lokasi::factory()->create(['nama_lokasi' => "Lokasi {$i}"]);
            $checklist = MasterChecklist::query()->create([
                'nama_checklist' => "Checklist {$i}",
                'lokasi_id' => $lokasi->id,
                'dibuat_oleh_id' => $petugas->id,
                'jenis_pengelola' => 'Petugas K3LH',
                'status' => 'Aktif',
            ]);
            $item = ItemChecklist::query()->create([
                'master_checklist_id' => $checklist->id,
                'nama_item' => "Item {$i}",
                'probability' => 3,
                'severity' => 3,
                'skor_risiko' => 9,
                'level_risiko' => 'Sedang',
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
            DetailInspeksi::query()->create([
                'inspeksi_k3l_id' => $inspeksi->id,
                'item_checklist_id' => $item->id,
                'status' => DetailInspeksi::STATUS_TIDAK,
                'skor_risiko_hasil' => $i * 2,
                'level_risiko_hasil' => 'Tinggi',
            ]);
        }

        $pimpinan = User::factory()->create(['role' => 'Pimpinan']);

        $this->actingAs($pimpinan)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lainnya (2 lokasi)')
            ->assertSee('Top 5 dari 7 lokasi.');

        Carbon::setTestNow();
    }

    public function test_satpam_sees_simple_insiden_dashboard(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $satpam = User::factory()->create(['role' => 'Satpam']);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Pos Satpam']);

        $laporan = LaporanInsiden::factory()->create([
            'satpam_id' => $satpam->id,
            'lokasi_id' => $lokasi->id,
            'jenis_insiden' => 'Kebakaran',
            'tanggal_waktu' => now(),
        ]);

        TindakLanjutInsiden::query()->create([
            'laporan_insiden_id' => $laporan->id,
            'status_perbaikan' => 'Dalam Proses',
        ]);

        $this->actingAs($satpam)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Satpam')
            ->assertSee('Buat Laporan Insiden')
            ->assertSee('Kebakaran')
            ->assertSee('Pos Satpam')
            ->assertSee('INS-'.str_pad((string) $laporan->id, 5, '0', STR_PAD_LEFT))
            ->assertDontSee('Dashboard Eksekutif')
            ->assertDontSee('Proporsi risiko K3LH');

        Carbon::setTestNow();
    }

    public function test_kalab_sees_simple_dashboard_with_sop_shortcut(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $lokasi = Lokasi::factory()->create([
            'nama_lokasi' => 'Lab Elektronika',
            'jenis_lokasi' => 'Laboratorium',
        ]);
        $kalab = User::factory()->create([
            'role' => 'Kalab',
            'lokasi_id' => $lokasi->id,
        ]);

        MasterChecklist::query()->create([
            'nama_checklist' => 'Checklist Lab Elektronika',
            'lokasi_id' => $lokasi->id,
            'dibuat_oleh_id' => $kalab->id,
            'jenis_pengelola' => 'Kalab',
            'status' => 'Aktif',
        ]);

        $this->actingAs($kalab)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Kalab')
            ->assertSee('Lab Elektronika')
            ->assertSee('Shortcut fitur')
            ->assertSee('Checklist Temuan')
            ->assertSee('Pemantauan B3')
            ->assertSee('Pedoman SOP')
            ->assertSee('Checklist temuan bahaya laboratorium Anda')
            ->assertDontSee('Dashboard Eksekutif')
            ->assertDontSee('Dashboard Satpam');

        Carbon::setTestNow();
    }
}
