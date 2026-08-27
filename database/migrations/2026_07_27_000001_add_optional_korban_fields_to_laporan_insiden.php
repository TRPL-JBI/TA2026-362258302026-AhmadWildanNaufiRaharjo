<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_insiden', function (Blueprint $table) {
            $table->string('usia_korban', 20)->nullable()->after('korban');
            $table->string('unit_prodi', 100)->nullable()->after('usia_korban');
            $table->string('jabatan_korban', 100)->nullable()->after('unit_prodi');
            $table->string('status_korban', 100)->nullable()->after('jabatan_korban');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_insiden', function (Blueprint $table) {
            $table->dropColumn(['usia_korban', 'unit_prodi', 'jabatan_korban', 'status_korban']);
        });
    }
};
