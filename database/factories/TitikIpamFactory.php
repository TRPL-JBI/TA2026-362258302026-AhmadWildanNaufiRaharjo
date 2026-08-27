<?php

namespace Database\Factories;

use App\Models\TitikIpam;
use App\Models\UnitIpam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitikIpam>
 */
class TitikIpamFactory extends Factory
{
    protected $model = TitikIpam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_ipam_id' => UnitIpam::factory(),
            'titik_lokasi' => fake()->unique()->words(2, true),
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
