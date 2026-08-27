<?php

namespace Tests\Feature\Apar;

use App\Models\Apar;
use App\Models\Lokasi;
use App\Models\Notifikasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AparExpiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_notifies_warning_apar_within_thirty_days(): void
    {
        Carbon::setTestNow('2026-06-01');

        $petugas = User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $lokasi = Lokasi::factory()->create(['nama_lokasi' => 'Gedung A']);
        $apar = Apar::factory()->forLokasi($lokasi)->create([
            'tanggal_expired' => '2026-06-20',
            'is_notified' => false,
        ]);

        $this->artisan('apar:check-expiry')
            ->assertSuccessful()
            ->expectsOutput('Selesai: 1 mendekati expired (≤30 hari), 0 sudah expired.');

        $this->assertTrue($apar->fresh()->is_notified);
        $this->assertSame(1, Notifikasi::query()->count());
        $this->assertSame($petugas->id, Notifikasi::query()->value('user_id'));
        $this->assertSame('Early Warning APAR', Notifikasi::query()->value('jenis_notifikasi'));
        $this->assertSame($apar->id, Notifikasi::query()->value('reference_id'));
        $this->assertStringStartsWith('APAR akan expired', Notifikasi::query()->value('judul'));
    }

    public function test_command_does_not_repeat_warning_notification(): void
    {
        Carbon::setTestNow('2026-06-01');

        User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        Apar::factory()->create([
            'tanggal_expired' => '2026-06-20',
            'is_notified' => true,
        ]);

        $this->artisan('apar:check-expiry')->assertSuccessful();

        $this->assertSame(0, Notifikasi::query()->count());
    }

    public function test_command_notifies_warning_apar_expiring_today(): void
    {
        Carbon::setTestNow('2026-06-01');

        User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        Apar::factory()->create([
            'tanggal_expired' => '2026-06-01',
            'is_notified' => false,
        ]);

        $this->artisan('apar:check-expiry')
            ->assertSuccessful()
            ->expectsOutput('Selesai: 1 mendekati expired (≤30 hari), 0 sudah expired.');

        $this->assertStringStartsWith('APAR akan expired', Notifikasi::query()->value('judul'));
    }

    public function test_command_notifies_both_warning_and_expired_in_same_run(): void
    {
        Carbon::setTestNow('2026-06-01');

        User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        Apar::factory()->create([
            'tanggal_expired' => '2026-06-15',
            'is_notified' => false,
        ]);
        Apar::factory()->create([
            'tanggal_expired' => '2026-05-10',
            'is_notified' => false,
        ]);

        $this->artisan('apar:check-expiry')
            ->assertSuccessful()
            ->expectsOutput('Selesai: 1 mendekati expired (≤30 hari), 1 sudah expired.');

        $this->assertSame(2, Notifikasi::query()->count());
    }

    public function test_command_notifies_expired_apar_once(): void
    {
        Carbon::setTestNow('2026-06-01');

        User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $apar = Apar::factory()->create([
            'tanggal_expired' => '2026-05-15',
            'is_notified' => false,
        ]);

        $this->artisan('apar:check-expiry')
            ->assertSuccessful()
            ->expectsOutput('Selesai: 0 mendekati expired (≤30 hari), 1 sudah expired.');

        $this->assertSame(1, Notifikasi::query()->count());
        $this->assertStringStartsWith('APAR sudah expired', Notifikasi::query()->value('judul'));

        $this->artisan('apar:check-expiry')->assertSuccessful();
        $this->assertSame(1, Notifikasi::query()->count());
    }

    public function test_petugas_can_fetch_and_mark_notifications(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $notifikasi = Notifikasi::query()->create([
            'user_id' => $petugas->id,
            'jenis_notifikasi' => 'Early Warning APAR',
            'judul' => 'APAR akan expired: APAR-TEST',
            'pesan' => 'Contoh pesan notifikasi.',
            'reference_id' => null,
            'is_read' => false,
        ]);

        $this->actingAs($petugas)
            ->getJson(route('notifikasi.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.id', $notifikasi->id);

        $this->actingAs($petugas)
            ->postJson(route('notifikasi.read', $notifikasi))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertTrue($notifikasi->fresh()->is_read);
    }

    public function test_user_cannot_mark_other_users_notification(): void
    {
        $owner = User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $notifikasi = Notifikasi::query()->create([
            'user_id' => $owner->id,
            'jenis_notifikasi' => 'Laporan Insiden',
            'judul' => 'Judul',
            'pesan' => 'Pesan',
            'reference_id' => null,
            'is_read' => false,
        ]);

        $this->actingAs($other)
            ->postJson(route('notifikasi.read', $notifikasi))
            ->assertForbidden();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
