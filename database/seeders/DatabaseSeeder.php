<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            [
                'username' => 'admin',
                'password' => 'password',
                'nama_lengkap' => 'Petugas K3LH Demo',
                'role' => 'Petugas K3LH',
            ],
            [
                'username' => 'kalab',
                'password' => 'password',
                'nama_lengkap' => 'Kepala Lab Demo',
                'role' => 'Kalab',
            ],
            [
                'username' => 'satpam',
                'password' => 'password',
                'nama_lengkap' => 'Satpam Demo',
                'role' => 'Satpam',
            ],
            [
                'username' => 'pimpinan',
                'password' => 'password',
                'nama_lengkap' => 'Pimpinan Demo',
                'role' => 'Pimpinan',
            ],
        ];

        foreach ($users as $data) {
            User::query()->updateOrCreate(
                ['username' => $data['username']],
                [
                    'password' => $data['password'],
                    'nama_lengkap' => $data['nama_lengkap'],
                    'role' => $data['role'],
                    'lokasi_id' => null,
                    'is_active' => true,
                ],
            );
        }

        $this->call(PatroliCaturwulanDemoSeeder::class);
    }
}
