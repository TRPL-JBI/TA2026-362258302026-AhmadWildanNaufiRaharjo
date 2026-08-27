<?php

namespace Tests\Feature\Apar;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_store_push_subscription(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);

        $this->actingAs($petugas)
            ->postJson(route('push.subscribe'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'key' => str_repeat('a', 87),
                'token' => str_repeat('b', 22),
                'encoding' => 'aesgcm',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Subscription push berhasil disimpan.');

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $petugas->id,
            'subscribable_type' => User::class,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ]);
    }

    public function test_petugas_can_delete_push_subscription(): void
    {
        $petugas = User::factory()->create(['role' => 'Petugas K3LH', 'is_active' => true]);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint';

        $petugas->updatePushSubscription($endpoint, str_repeat('a', 87), str_repeat('b', 22), 'aesgcm');

        $this->actingAs($petugas)
            ->deleteJson(route('push.unsubscribe'), [
                'endpoint' => $endpoint,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Subscription push berhasil dihapus.');

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }
}
