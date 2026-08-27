<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tindak_lanjut_inspeksi', function (Blueprint $table) {
            $table->dateTime('tanggal_selesai')->nullable()->after('tanggal_tindakan');
            $table->index('tanggal_selesai');
        });

        Schema::table('tindak_lanjut_insiden', function (Blueprint $table) {
            $table->dateTime('tanggal_selesai')->nullable()->after('tanggal_tindakan');
            $table->index('tanggal_selesai');
        });
    }

    public function down(): void
    {
        Schema::table('tindak_lanjut_inspeksi', function (Blueprint $table) {
            $table->dropIndex(['tanggal_selesai']);
            $table->dropColumn('tanggal_selesai');
        });

        Schema::table('tindak_lanjut_insiden', function (Blueprint $table) {
            $table->dropIndex(['tanggal_selesai']);
            $table->dropColumn('tanggal_selesai');
        });
    }
};
