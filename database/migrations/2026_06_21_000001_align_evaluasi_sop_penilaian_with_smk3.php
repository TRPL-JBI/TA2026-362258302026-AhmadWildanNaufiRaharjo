<?php

use App\Support\EvaluasiSopPenilaian;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_sop', function (Blueprint $table) {
            $table->string('tingkat_penerapan', 30)->nullable()->after('persentase_kepatuhan');
        });

        DB::table('detail_evaluasi_sop')
            ->where('penilaian', 'Major')
            ->update(['penilaian' => 'Mayor']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE detail_evaluasi_sop MODIFY penilaian ENUM('Sesuai', 'Kritikal', 'Mayor', 'Minor') NULL");
        }

        foreach (DB::table('evaluasi_sop')->select('id')->cursor() as $evaluasi) {
            $penilaianValues = DB::table('detail_evaluasi_sop')
                ->where('evaluasi_sop_id', $evaluasi->id)
                ->pluck('penilaian')
                ->map(fn ($penilaian) => EvaluasiSopPenilaian::normalize($penilaian))
                ->all();

            $hasil = EvaluasiSopPenilaian::evaluate($penilaianValues);

            DB::table('evaluasi_sop')
                ->where('id', $evaluasi->id)
                ->update([
                    'persentase_kepatuhan' => $hasil['persentase_kepatuhan'] ?? 0,
                    'tingkat_penerapan' => $hasil['tingkat_penerapan'],
                ]);
        }
    }

    public function down(): void
    {
        DB::table('detail_evaluasi_sop')
            ->where('penilaian', 'Mayor')
            ->update(['penilaian' => 'Major']);

        Schema::table('evaluasi_sop', function (Blueprint $table) {
            $table->dropColumn('tingkat_penerapan');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE detail_evaluasi_sop MODIFY penilaian ENUM('Sesuai', 'Kritikal', 'Major', 'Minor') NULL");
        }
    }
};
