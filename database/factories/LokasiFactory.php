<?php

namespace Database\Factories;

use App\Models\Lokasi;
use App\Services\LokasiQrCodeService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lokasi>
 */
class LokasiFactory extends Factory
{
    protected $model = Lokasi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(['Gedung', 'Laboratorium', 'Ruangan']);

        return [
            'kode_lokasi' => LokasiQrCodeService::generateKodeLokasi($jenis),
            'nama_lokasi' => fake()->unique()->words(3, true),
            'jenis_lokasi' => $jenis,
            'deskripsi' => fake()->optional()->sentence(),
            'qr_code_path' => null,
        ];
    }
}
