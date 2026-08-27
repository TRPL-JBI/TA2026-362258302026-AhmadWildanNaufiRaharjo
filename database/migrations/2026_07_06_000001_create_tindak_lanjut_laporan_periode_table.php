<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tindak_lanjut_laporan_periode', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('caturwulan')->comment('1-3');
            $table->enum('status', ['Berlangsung', 'Selesai'])->default('Berlangsung');
            $table->unsignedInteger('selesai_by_id')->nullable();
            $table->dateTime('selesai_at')->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'caturwulan'], 'tindak_lanjut_laporan_periode_unique');
            $table->index('status');
            $table->index('selesai_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjut_laporan_periode');
    }
};
