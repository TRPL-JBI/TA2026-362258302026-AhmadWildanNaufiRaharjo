<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_limbah_b3', function (Blueprint $table) {
            $table->enum('status', ['Berlangsung', 'Selesai'])
                ->default('Berlangsung')
                ->after('tahun');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_limbah_b3', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
