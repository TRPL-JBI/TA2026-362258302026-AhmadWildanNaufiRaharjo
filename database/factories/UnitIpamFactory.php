<?php

namespace Database\Factories;

use App\Models\UnitIpam;
use App\Services\UnitIpamCodeService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitIpam>
 */
class UnitIpamFactory extends Factory
{
    protected $model = UnitIpam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_unit' => UnitIpamCodeService::generateKodeUnit(),
            'nama_unit' => 'IPAM '.fake()->unique()->numberBetween(1, 99),
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
