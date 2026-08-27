<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kriteria_audit_sop', function (Blueprint $table) {
            $table->unsignedSmallInteger('urutan')->default(0)->after('nomor_kriteria');
        });

        $path = database_path('seeders/data/kriteria_audit_sop.json');

        if (! is_file($path)) {
            return;
        }

        $kriteria = json_decode((string) file_get_contents($path), true);

        if (! is_array($kriteria)) {
            return;
        }

        foreach ($kriteria as $index => $row) {
            $nomor = trim((string) ($row['nomor_kriteria'] ?? ''));

            if ($nomor === '') {
                continue;
            }

            DB::table('kriteria_audit_sop')
                ->where('nomor_kriteria', $nomor)
                ->update(['urutan' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('kriteria_audit_sop', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }
};
