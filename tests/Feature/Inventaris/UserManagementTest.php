<?php

namespace Tests\Feature\Inventaris;

use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_create_user(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        $response = $this->actingAs($petugas)->post(route('inventaris.user.store'), [
            'username' => 'budi.k3lh',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Budi Santoso',
            'role' => 'Petugas K3LH',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('inventaris.user'));
        $response->assertSessionHas('success');

        $created = User::query()->where('username', 'budi.k3lh')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('rahasia123', $created->password));
        $this->assertNull($created->lokasi_id);
    }

    public function test_petugas_can_create_kalab_with_laboratorium(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $lab = Lokasi::factory()->create([
            'nama_lokasi' => 'Lab Kimia',
            'jenis_lokasi' => 'Laboratorium',
        ]);

        $this->actingAs($petugas)->post(route('inventaris.user.store'), [
            'username' => 'dr.sari',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Dr. Sari',
            'role' => 'Kalab',
            'lokasi_id' => $lab->id,
            'is_active' => '1',
        ])->assertRedirect(route('inventaris.user'));

        $kalab = User::query()->where('username', 'dr.sari')->first();
        $this->assertSame($lab->id, $kalab->lokasi_id);
    }

    public function test_user_list_does_not_expose_password(): void
    {
        $petugas = User::factory()->create([
            'role' => 'Petugas K3LH',
            'password' => 'password',
        ]);

        $this->actingAs($petugas)
            ->get(route('inventaris.user'))
            ->assertOk()
            ->assertSee($petugas->username)
            ->assertDontSee('$2y$')
            ->assertDontSee('bcrypt:');
    }

    public function test_petugas_can_update_user_without_changing_password(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $target = User::factory()->create([
            'username' => 'target.user',
            'password' => 'password-lama',
            'nama_lengkap' => 'Nama Lama',
            'role' => 'Satpam',
        ]);
        $hashBefore = $target->password;

        $this->actingAs($petugas)->put(route('inventaris.user.update', $target), [
            'username' => 'target.user',
            'nama_lengkap' => 'Nama Baru',
            'role' => 'Satpam',
            'is_active' => '1',
        ])->assertRedirect(route('inventaris.user'));

        $target->refresh();
        $this->assertSame('Nama Baru', $target->nama_lengkap);
        $this->assertSame($hashBefore, $target->password);
    }

    public function test_petugas_can_update_password(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);
        $target = User::factory()->create(['role' => 'Satpam', 'password' => 'password-lama']);

        $this->actingAs($petugas)->put(route('inventaris.user.update', $target), [
            'username' => $target->username,
            'password' => 'password-baru',
            'nama_lengkap' => $target->nama_lengkap,
            'role' => 'Satpam',
            'is_active' => '1',
        ])->assertRedirect(route('inventaris.user'));

        $this->assertTrue(Hash::check('password-baru', $target->refresh()->password));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($petugas)
            ->delete(route('inventaris.user.destroy', $petugas))
            ->assertRedirect(route('inventaris.user'))
            ->assertSessionHas('error');

        $this->assertModelExists($petugas);
    }

    public function test_satpam_cannot_store_user(): void
    {
        $satpam = User::factory()->create(['role' => 'Satpam']);

        $this->actingAs($satpam)->post(route('inventaris.user.store'), [
            'username' => 'baru',
            'password' => 'rahasia123',
            'nama_lengkap' => 'User Baru',
            'role' => 'Satpam',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('users', ['username' => 'baru']);
    }

    public function test_store_validates_required_fields(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH']);

        $this->actingAs($petugas)->post(route('inventaris.user.store'), [])
            ->assertSessionHasErrors(['username', 'password', 'nama_lengkap', 'role']);
    }
}
