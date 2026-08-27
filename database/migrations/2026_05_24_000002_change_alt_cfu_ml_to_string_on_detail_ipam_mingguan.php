<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE detail_ipam_mingguan MODIFY alt_cfu_ml VARCHAR(100) NULL COMMENT \'Angka Lempeng Total (cfu/ml), contoh: 5,50 x 10²\'');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE detail_ipam_mingguan MODIFY alt_cfu_ml DECIMAL(10, 2) NULL COMMENT \'Angka Lempeng Total\'');
    }
};
