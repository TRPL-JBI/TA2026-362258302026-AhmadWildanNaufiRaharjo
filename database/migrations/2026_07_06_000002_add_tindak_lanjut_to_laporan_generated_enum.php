<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `laporan_generated` MODIFY COLUMN `jenis_laporan` ENUM('K3L', 'Insiden', 'IPAM', 'IPAL', 'Limbah B3', 'Evaluasi SOP', 'Inventaris APAR', 'Tindak Lanjut') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `laporan_generated` MODIFY COLUMN `jenis_laporan` ENUM('K3L', 'Insiden', 'IPAM', 'IPAL', 'Limbah B3', 'Evaluasi SOP', 'Inventaris APAR') NOT NULL");
    }
};
