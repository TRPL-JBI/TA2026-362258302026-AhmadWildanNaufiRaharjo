<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tindak_lanjut_laporan_periode', function (Blueprint $table) {
            $table->json('items_snapshot')->nullable()->after('selesai_at');
        });
    }

    public function down(): void
    {
        Schema::table('tindak_lanjut_laporan_periode', function (Blueprint $table) {
            $table->dropColumn('items_snapshot');
        });
    }
};
