<?php

namespace Database\Factories;

use App\Models\LaporanIpal;
use App\Models\User;
use App\Support\IpalTriwulan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanIpal>
 */
class LaporanIpalFactory extends Factory
{
    protected $model = LaporanIpal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petugas_id' => User::factory(),
            'triwulan' => fake()->numberBetween(1, 4),
            'tahun' => (int) date('Y'),
            'evaluasi_kinerja' => null,
            'status' => IpalTriwulan::STATUS_BERLANGSUNG,
        ];
    }
}
