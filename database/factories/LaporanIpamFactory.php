<?php

namespace Database\Factories;

use App\Models\LaporanIpam;
use App\Models\TitikIpam;
use App\Models\User;
use App\Support\IpamBulan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanIpam>
 */
class LaporanIpamFactory extends Factory
{
    protected $model = LaporanIpam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titik_ipam_id' => TitikIpam::factory(),
            'petugas_id' => User::factory(),
            'bulan' => fake()->numberBetween(1, 12),
            'tahun' => (int) date('Y'),
            'kesimpulan' => null,
            'status' => IpamBulan::STATUS_BERLANGSUNG,
        ];
    }
}
