<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_evaluasi_sop', function (Blueprint $table) {
            $table->string('nomor_kriteria', 50)->nullable()->after('kriteria_audit_id');
            $table->unsignedInteger('urutan_kriteria')->nullable()->after('nomor_kriteria');
            $table->text('uraian_kriteria')->nullable()->after('urutan_kriteria');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('
                UPDATE detail_evaluasi_sop AS d
                INNER JOIN kriteria_audit_sop AS k ON k.id = d.kriteria_audit_id
                SET
                    d.nomor_kriteria = k.nomor_kriteria,
                    d.urutan_kriteria = k.urutan,
                    d.uraian_kriteria = k.uraian_kriteria
                WHERE d.nomor_kriteria IS NULL
            ');
        } else {
            foreach (DB::table('detail_evaluasi_sop')->whereNull('nomor_kriteria')->cursor() as $detail) {
                $kriteria = DB::table('kriteria_audit_sop')->where('id', $detail->kriteria_audit_id)->first();

                if ($kriteria === null) {
                    continue;
                }

                DB::table('detail_evaluasi_sop')
                    ->where('id', $detail->id)
                    ->update([
                        'nomor_kriteria' => $kriteria->nomor_kriteria,
                        'urutan_kriteria' => $kriteria->urutan,
                        'uraian_kriteria' => $kriteria->uraian_kriteria,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('detail_evaluasi_sop', function (Blueprint $table) {
            $table->dropColumn(['nomor_kriteria', 'urutan_kriteria', 'uraian_kriteria']);
        });
    }
};
