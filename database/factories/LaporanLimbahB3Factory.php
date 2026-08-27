<?php

namespace Database\Factories;

use App\Models\LaporanLimbahB3;
use App\Models\User;
use App\Support\B3Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanLimbahB3>
 */
class LaporanLimbahB3Factory extends Factory
{
    protected $model = LaporanLimbahB3::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petugas_id' => User::factory(),
            'semester' => fake()->numberBetween(1, 2),
            'tahun' => (int) date('Y'),
            'status' => B3Semester::STATUS_BERLANGSUNG,
        ];
    }
}
