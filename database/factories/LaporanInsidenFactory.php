<?php

namespace Database\Factories;

use App\Models\LaporanInsiden;
use App\Models\Lokasi;
use App\Models\User;
use App\Support\LaporanInsidenJenis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanInsiden>
 */
class LaporanInsidenFactory extends Factory
{
    protected $model = LaporanInsiden::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'satpam_id' => User::factory()->create(['role' => 'Satpam']),
            'lokasi_id' => Lokasi::factory(),
            'lokasi_manual' => null,
            'jenis_insiden' => fake()->randomElement(LaporanInsidenJenis::all()),
            'tanggal_waktu' => fake()->dateTimeBetween('-1 month'),
            'kronologi' => fake()->paragraph(),
            'korban' => null,
            'foto_path' => null,
        ];
    }

    public function manualLocation(string $lokasi = 'Area parkir belakang'): static
    {
        return $this->state(fn () => [
            'lokasi_id' => null,
            'lokasi_manual' => $lokasi,
        ]);
    }
}
