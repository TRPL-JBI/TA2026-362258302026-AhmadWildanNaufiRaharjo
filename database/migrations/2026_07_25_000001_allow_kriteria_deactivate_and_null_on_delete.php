<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('detail_evaluasi_sop', 'detail_evaluasi_sop_kriteria_audit_id_foreign', ['kriteria_audit_id']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE detail_evaluasi_sop MODIFY kriteria_audit_id INT UNSIGNED NULL');
        } else {
            Schema::table('detail_evaluasi_sop', function (Blueprint $table) {
                $table->unsignedInteger('kriteria_audit_id')->nullable()->change();
            });
        }

        Schema::table('detail_evaluasi_sop', function (Blueprint $table) {
            $table->foreign('kriteria_audit_id')
                ->references('id')
                ->on('kriteria_audit_sop')
                ->nullOnDelete();
        });

        $this->dropUniqueIfExists('kriteria_audit_sop', ['lokasi_id', 'nomor_kriteria']);

        Schema::table('kriteria_audit_sop', function (Blueprint $table) {
            $table->index(['lokasi_id', 'nomor_kriteria'], 'kriteria_audit_sop_lokasi_nomor_idx');
        });
    }

    public function down(): void
    {
        $this->dropForeignIfExists('detail_evaluasi_sop', 'detail_evaluasi_sop_kriteria_audit_id_foreign', ['kriteria_audit_id']);

        Schema::table('kriteria_audit_sop', function (Blueprint $table) {
            $table->dropIndex('kriteria_audit_sop_lokasi_nomor_idx');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE detail_evaluasi_sop MODIFY kriteria_audit_id INT UNSIGNED NOT NULL');
        } else {
            Schema::table('detail_evaluasi_sop', function (Blueprint $table) {
                $table->unsignedInteger('kriteria_audit_id')->nullable(false)->change();
            });
        }

        Schema::table('detail_evaluasi_sop', function (Blueprint $table) {
            $table->foreign('kriteria_audit_id')
                ->references('id')
                ->on('kriteria_audit_sop');
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropForeignIfExists(string $table, string $name, array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropForeign($columns);
            });
        } catch (\Throwable) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($name) {
                    $blueprint->dropForeign($name);
                });
            } catch (\Throwable) {
                // sudah tidak ada
            }
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropUniqueIfExists(string $table, array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropUnique($columns);
            });
        } catch (\Throwable) {
            // sudah tidak ada / nama berbeda
        }
    }
};
