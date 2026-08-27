<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kriteria_audit_sop', 'lokasi_id')) {
            Schema::table('kriteria_audit_sop', function (Blueprint $table) {
                $table->unsignedInteger('lokasi_id')->nullable()->after('id');
                $table->string('dikelola_oleh', 30)->default('Kalab')->after('lokasi_id');
            });
        }

        if (! Schema::hasColumn('evaluasi_sop', 'jenis_evaluasi')) {
            Schema::table('evaluasi_sop', function (Blueprint $table) {
                $table->string('jenis_evaluasi', 20)->default('laboratorium')->after('lokasi_id');
                $table->unsignedInteger('petugas_k3lh_id')->nullable()->after('kalab_id');
            });
        }

        $this->migrateGlobalKriteriaToPerLokasi();

        if (! $this->foreignKeyExists('kriteria_audit_sop', 'kriteria_audit_sop_lokasi_id_foreign')) {
            Schema::table('kriteria_audit_sop', function (Blueprint $table) {
                $table->foreign('lokasi_id')->references('id')->on('lokasi')->cascadeOnDelete();
            });
        }

        if (! $this->indexExists('kriteria_audit_sop', 'kriteria_audit_sop_lokasi_id_nomor_kriteria_unique')) {
            Schema::table('kriteria_audit_sop', function (Blueprint $table) {
                $table->unique(['lokasi_id', 'nomor_kriteria']);
            });
        }

        if (! $this->foreignKeyExists('evaluasi_sop', 'evaluasi_sop_petugas_k3lh_id_foreign')) {
            Schema::table('evaluasi_sop', function (Blueprint $table) {
                $table->foreign('petugas_k3lh_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE evaluasi_sop MODIFY kalab_id INT UNSIGNED NULL');
        } elseif (! $this->columnIsNullable('evaluasi_sop', 'kalab_id')) {
            Schema::table('evaluasi_sop', function (Blueprint $table) {
                $table->unsignedInteger('kalab_id')->nullable()->change();
            });
        }

        DB::table('evaluasi_sop')
            ->where('tingkat_penerapan', 'Belum Berhasil')
            ->update(['tingkat_penerapan' => 'Tidak Memenuhi']);
    }

    public function down(): void
    {
        DB::table('evaluasi_sop')
            ->where('tingkat_penerapan', 'Tidak Memenuhi')
            ->update(['tingkat_penerapan' => 'Belum Berhasil']);

        if ($this->foreignKeyExists('evaluasi_sop', 'evaluasi_sop_petugas_k3lh_id_foreign')) {
            Schema::table('evaluasi_sop', function (Blueprint $table) {
                $table->dropForeign(['petugas_k3lh_id']);
            });
        }

        if (Schema::hasColumn('evaluasi_sop', 'jenis_evaluasi')) {
            Schema::table('evaluasi_sop', function (Blueprint $table) {
                $table->dropColumn(['jenis_evaluasi', 'petugas_k3lh_id']);
            });
        }

        if ($this->foreignKeyExists('kriteria_audit_sop', 'kriteria_audit_sop_lokasi_id_foreign')) {
            Schema::table('kriteria_audit_sop', function (Blueprint $table) {
                $table->dropForeign(['lokasi_id']);
            });
        }

        if ($this->indexExists('kriteria_audit_sop', 'kriteria_audit_sop_lokasi_id_nomor_kriteria_unique')) {
            Schema::table('kriteria_audit_sop', function (Blueprint $table) {
                $table->dropUnique(['lokasi_id', 'nomor_kriteria']);
            });
        }

        if (Schema::hasColumn('kriteria_audit_sop', 'lokasi_id')) {
            Schema::table('kriteria_audit_sop', function (Blueprint $table) {
                $table->dropColumn(['lokasi_id', 'dikelola_oleh']);
            });
        }
    }

    private function migrateGlobalKriteriaToPerLokasi(): void
    {
        $globalRows = DB::table('kriteria_audit_sop')->whereNull('lokasi_id')->get();

        if ($globalRows->isEmpty()) {
            return;
        }

        $template = $this->kriteriaTemplate($globalRows);

        $lokasiIds = $this->lokasiIdsForKriteriaMigration();

        $newIdByLokasiAndNomor = [];

        foreach ($lokasiIds as $lokasiId) {
            $dikelolaOleh = $this->pengelolaForLokasi((int) $lokasiId);

            foreach ($template as $index => $row) {
                $nomor = trim((string) ($row['nomor_kriteria'] ?? ''));

                if ($nomor === '') {
                    continue;
                }

                $existing = DB::table('kriteria_audit_sop')
                    ->where('lokasi_id', $lokasiId)
                    ->where('nomor_kriteria', $nomor)
                    ->value('id');

                if ($existing) {
                    $newIdByLokasiAndNomor[$lokasiId][$nomor] = (int) $existing;

                    continue;
                }

                $newId = DB::table('kriteria_audit_sop')->insertGetId([
                    'lokasi_id' => $lokasiId,
                    'dikelola_oleh' => $dikelolaOleh,
                    'nomor_kriteria' => $nomor,
                    'urutan' => $index + 1,
                    'uraian_kriteria' => trim((string) ($row['uraian_kriteria'] ?? '')),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $newIdByLokasiAndNomor[$lokasiId][$nomor] = $newId;
            }
        }

        $globalIds = $globalRows->pluck('id')->all();

        foreach (DB::table('detail_evaluasi_sop')
            ->whereIn('kriteria_audit_id', $globalIds)
            ->select('id', 'evaluasi_sop_id', 'kriteria_audit_id')
            ->cursor() as $detail) {
            $evaluasi = DB::table('evaluasi_sop')->where('id', $detail->evaluasi_sop_id)->first();

            if ($evaluasi === null || $evaluasi->lokasi_id === null) {
                continue;
            }

            $globalRow = $globalRows->firstWhere('id', $detail->kriteria_audit_id);

            if ($globalRow === null) {
                continue;
            }

            $nomor = trim((string) $globalRow->nomor_kriteria);
            $lokasiId = (int) $evaluasi->lokasi_id;

            $newKriteriaId = $newIdByLokasiAndNomor[$lokasiId][$nomor] ?? DB::table('kriteria_audit_sop')
                ->where('lokasi_id', $lokasiId)
                ->where('nomor_kriteria', $nomor)
                ->value('id');

            if ($newKriteriaId === null) {
                $newKriteriaId = DB::table('kriteria_audit_sop')->insertGetId([
                    'lokasi_id' => $lokasiId,
                    'dikelola_oleh' => $this->pengelolaForLokasi($lokasiId),
                    'nomor_kriteria' => $nomor,
                    'urutan' => (int) ($globalRow->urutan ?? 0) ?: 9999,
                    'uraian_kriteria' => trim((string) $globalRow->uraian_kriteria),
                    'is_active' => (bool) ($globalRow->is_active ?? true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $newIdByLokasiAndNomor[$lokasiId][$nomor] = (int) $newKriteriaId;
            }

            DB::table('detail_evaluasi_sop')
                ->where('id', $detail->id)
                ->update(['kriteria_audit_id' => $newKriteriaId]);
        }

        $stillReferenced = DB::table('detail_evaluasi_sop')
            ->whereIn('kriteria_audit_id', $globalIds)
            ->exists();

        if ($stillReferenced) {
            throw new RuntimeException(
                'Migrasi evaluasi SOP gagal: masih ada detail evaluasi yang mereferensikan kriteria global. '
                .'Pastikan setiap evaluasi memiliki lokasi_id yang valid.',
            );
        }

        DB::table('kriteria_audit_sop')->whereNull('lokasi_id')->delete();
    }

    /**
     * @return list<int>
     */
    private function lokasiIdsForKriteriaMigration(): array
    {
        $fromEvaluasi = DB::table('evaluasi_sop')
            ->whereNotNull('lokasi_id')
            ->distinct()
            ->pluck('lokasi_id');

        $fromLaboratorium = DB::table('lokasi')
            ->where('jenis_lokasi', 'Laboratorium')
            ->pluck('id');

        return $fromEvaluasi
            ->merge($fromLaboratorium)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<array{nomor_kriteria: string, uraian_kriteria: string}>
     */
    private function kriteriaTemplate(Collection $globalRows): array
    {
        $templatePath = database_path('seeders/data/kriteria_audit_sop.json');

        if (is_file($templatePath)) {
            $decoded = json_decode((string) file_get_contents($templatePath), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $globalRows
            ->map(fn ($row) => [
                'nomor_kriteria' => (string) $row->nomor_kriteria,
                'uraian_kriteria' => (string) $row->uraian_kriteria,
            ])
            ->values()
            ->all();
    }

    private function pengelolaForLokasi(int $lokasiId): string
    {
        $jenisLokasi = DB::table('lokasi')->where('id', $lokasiId)->value('jenis_lokasi');

        return $jenisLokasi === 'Laboratorium' ? 'Kalab' : 'Petugas K3LH';
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        $nullable = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('IS_NULLABLE');

        return $nullable === 'YES';
    }
};
