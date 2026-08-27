<?php

namespace Database\Factories;

use App\Models\SopDokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopDokumen>
 */
class SopDokumenFactory extends Factory
{
    protected $model = SopDokumen::class;

    public function definition(): array
    {
        return [
            'judul' => fake()->words(3, true),
            'deskripsi' => fake()->optional()->sentence(),
            'file_path' => 'sop-dokumen/demo.pdf',
            'original_filename' => 'demo.pdf',
            'uploaded_by' => User::factory(),
        ];
    }
}
