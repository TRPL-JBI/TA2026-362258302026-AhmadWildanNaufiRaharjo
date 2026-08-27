<?php

namespace Database\Factories;

use App\Models\Apar;
use App\Models\Lokasi;
use App\Services\AparQrCodeService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apar>
 */
class AparFactory extends Factory
{
    protected $model = Apar::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lokasi_id' => Lokasi::factory(),
            'kode_apar' => 'APAR-SEED-'.fake()->unique()->numerify('######'),
            'jenis_apar' => fake()->randomElement(['Powder', 'CO2', 'Foam']),
            'kapasitas_kg' => fake()->randomElement([3, 5, 6, 9]),
            'tanggal_expired' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'status_kondisi' => null,
            'keterangan' => fake()->optional()->sentence(),
            'qr_code_path' => null,
            'is_notified' => false,
        ];
    }

    public function forLokasi(Lokasi $lokasi): static
    {
        return $this->state(fn () => [
            'lokasi_id' => $lokasi->id,
            'kode_apar' => AparQrCodeService::generateKodeApar($lokasi),
        ]);
    }
}
