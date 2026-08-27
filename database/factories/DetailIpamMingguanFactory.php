<?php

namespace Database\Factories;

use App\Models\DetailIpamMingguan;
use App\Models\LaporanIpam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailIpamMingguan>
 */
class DetailIpamMingguanFactory extends Factory
{
    protected $model = DetailIpamMingguan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'laporan_ipam_id' => LaporanIpam::factory(),
            'minggu_ke' => fake()->numberBetween(1, 4),
            'suhu_celcius' => null,
            'ph' => fake()->randomFloat(1, 6.5, 8.5),
            'alt_cfu_ml' => '5,50 x 10²',
            'salmonella' => 'Negatif',
            'status' => 'Baik',
            'kendala' => null,
            'rekomendasi' => null,
        ];
    }
}
