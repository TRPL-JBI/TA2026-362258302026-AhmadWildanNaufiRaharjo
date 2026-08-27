<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patroli_laporan_periode', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('petugas_id');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('caturwulan')->comment('1-3');
            $table->enum('jenis', ['temuan', 'apar']);
            $table->enum('status', ['Berlangsung', 'Selesai'])->default('Berlangsung');
            $table->dateTime('selesai_at')->nullable();
            $table->timestamps();

            $table->unique(['petugas_id', 'tahun', 'caturwulan', 'jenis'], 'patroli_laporan_periode_unique');
            $table->index('petugas_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patroli_laporan_periode');
    }
};
