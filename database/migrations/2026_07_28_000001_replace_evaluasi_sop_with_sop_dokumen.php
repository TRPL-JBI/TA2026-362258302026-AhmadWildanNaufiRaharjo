<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('validasi_evaluasi_sop');
        Schema::dropIfExists('detail_evaluasi_sop');
        Schema::dropIfExists('evaluasi_sop');
        Schema::dropIfExists('kriteria_audit_sop');

        if (Schema::hasTable('laporan_generated')) {
            DB::table('laporan_generated')
                ->where('jenis_laporan', 'Evaluasi SOP')
                ->delete();
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasTable('laporan_generated')) {
            DB::statement("ALTER TABLE `laporan_generated` MODIFY COLUMN `jenis_laporan` ENUM('K3L', 'Insiden', 'IPAM', 'IPAL', 'Limbah B3', 'Inventaris APAR', 'Tindak Lanjut') NOT NULL");
        }

        Schema::create('sop_dokumen', function (Blueprint $table) {
            $table->increments('id');
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedInteger('uploaded_by');
            $table->timestamps();

            $table->index('uploaded_by');
            $table->foreign('uploaded_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_dokumen');

        Schema::create('kriteria_audit_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lokasi_id')->nullable();
            $table->string('nomor_kriteria', 50);
            $table->unsignedInteger('urutan')->default(0);
            $table->text('uraian_kriteria');
            $table->string('dikelola_oleh', 30)->default('Kalab');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('evaluasi_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kalab_id')->nullable();
            $table->unsignedInteger('petugas_k3lh_id')->nullable();
            $table->unsignedInteger('lokasi_id');
            $table->string('jenis_evaluasi', 20)->default('laboratorium');
            $table->string('unit_laboratorium', 100);
            $table->date('tanggal_evaluasi');
            $table->decimal('persentase_kepatuhan', 5, 2)->nullable();
            $table->string('tingkat_penerapan', 30)->nullable();
            $table->text('catatan_evaluasi')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_evaluasi_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('evaluasi_sop_id');
            $table->unsignedInteger('kriteria_audit_id')->nullable();
            $table->string('nomor_kriteria', 50)->nullable();
            $table->unsignedInteger('urutan_kriteria')->nullable();
            $table->text('uraian_kriteria')->nullable();
            $table->string('penilaian', 20)->nullable();
            $table->text('catatan_detail')->nullable();
            $table->timestamps();
        });

        Schema::create('validasi_evaluasi_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('evaluasi_sop_id')->unique();
            $table->unsignedInteger('petugas_k3lh_id');
            $table->dateTime('tanggal_validasi');
            $table->text('komentar_validasi')->nullable();
            $table->boolean('is_approved');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasTable('laporan_generated')) {
            DB::statement("ALTER TABLE `laporan_generated` MODIFY COLUMN `jenis_laporan` ENUM('K3L', 'Insiden', 'IPAM', 'IPAL', 'Limbah B3', 'Evaluasi SOP', 'Inventaris APAR', 'Tindak Lanjut') NOT NULL");
        }
    }
};
