<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_insiden', function (Blueprint $table) {
            $table->text('korban')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_insiden', function (Blueprint $table) {
            $table->string('korban', 200)->nullable()->change();
        });
    }
};
