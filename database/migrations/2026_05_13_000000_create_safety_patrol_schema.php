<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 50)->unique();
            $table->string('password', 255)->comment('Encrypted password');
            $table->rememberToken();
            $table->string('nama_lengkap', 100)->comment('[1] Tambahan baru');
            $table->enum('role', ['Petugas K3LH', 'Satpam', 'Kalab', 'Pimpinan']);
            $table->unsignedInteger('lokasi_id')->nullable()->comment('[1] Tambahan baru — HANYA untuk role Kalab. NULL untuk role lain. Menentukan lab yang menjadi tanggung jawabnya.');
            $table->boolean('is_active')->default(true);
            $this->timestamps($table);

            $table->index('role');
            $table->index('is_active');
            $table->index('lokasi_id');
        });

        Schema::create('lokasi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode_lokasi', 50)->unique()->comment('Contoh: GKT-L1-R101');
            $table->string('nama_lokasi', 100)->comment('Contoh: Gedung Kuliah Terpadu');
            $table->enum('jenis_lokasi', ['Gedung', 'Laboratorium', 'Ruangan'])->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('qr_code_path', 255)->nullable()->comment('Path file QR Code yang digenerate');
            $this->timestamps($table);

            $table->index('kode_lokasi');
        });

        Schema::create('apar', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lokasi_id');
            $table->string('kode_apar', 50)->unique()->comment('Contoh: APAR-GKT-001');
            $table->enum('jenis_apar', ['Powder', 'CO2', 'Foam']);
            $table->decimal('kapasitas_kg', 5, 2)->comment('Kapasitas dalam kilogram');
            $table->date('tanggal_expired');
            $table->enum('status_kondisi', ['Baik Tersegel', 'Terbuka'])->nullable()->comment('Diisi saat patroli APAR');
            $table->text('keterangan')->nullable();
            $table->string('qr_code_path', 255)->nullable()->comment('Path file QR Code yang digenerate');
            $table->boolean('is_notified')->default(false)->comment('Flag early warning — set true setelah notifikasi terkirim, cegah duplikasi');
            $this->timestamps($table);

            $table->index('lokasi_id');
            $table->index('kode_apar');
            $table->index('tanggal_expired');
            $table->index('status_kondisi');
        });

        Schema::create('unit_ipam', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode_unit', 50)->unique()->comment('Contoh: IPM-01');
            $table->string('nama_unit', 100)->comment('Contoh: IPAM 1');
            $table->text('deskripsi')->nullable();
            $this->timestamps($table);

            $table->index('kode_unit');
        });

        Schema::create('titik_ipam', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('unit_ipam_id');
            $table->string('titik_lokasi', 50)->comment('Contoh: Kantin, Belakang Gedung PRATA');
            $table->text('deskripsi')->nullable();
            $this->timestamps($table);

            $table->index('unit_ipam_id');
            $table->unique(['unit_ipam_id', 'titik_lokasi']);
        });

        Schema::create('master_checklist', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_checklist', 100)->comment('Contoh: Checklist Lab Kimia 2026');
            $table->unsignedInteger('lokasi_id')->comment('Lokasi yang diawasi checklist ini');
            $table->unsignedInteger('dibuat_oleh_id')->comment('[4] Bisa Petugas K3LH (area umum/gedung) atau Kalab (lab-nya sendiri)');
            $table->enum('jenis_pengelola', ['Petugas K3LH', 'Kalab'])->comment('Menandai siapa yang membuat dan bertanggung jawab atas checklist ini');
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif')->comment('Nonaktif = tidak muncul saat inspeksi');
            $this->timestamps($table);

            $table->index('lokasi_id');
            $table->index('dibuat_oleh_id');
            $table->index('jenis_pengelola');
            $table->index('status');
        });

        Schema::create('item_checklist', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('master_checklist_id');
            $table->string('nama_item', 200)->comment('Contoh: Kabel instalasi terkelupas');
            $table->text('deskripsi')->nullable()->comment('Penjelasan kriteria penilaian item (opsional)');
            $table->tinyInteger('probability')->comment('Nilai P 1-5, ditetapkan oleh pembuat checklist');
            $table->tinyInteger('severity')->comment('Nilai S 1-5, ditetapkan oleh pembuat checklist');
            $table->tinyInteger('skor_risiko')->comment('AUTO-CALCULATED: P x S, disimpan sebagai cache');
            $table->enum('level_risiko', ['Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'])->comment('AUTO-CALCULATED dari skor_risiko');
            $table->integer('urutan')->nullable()->comment('Nomor urut tampilan item dalam checklist');
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $this->timestamps($table);

            $table->index('master_checklist_id');
            $table->index('status');
            $table->index('level_risiko');
        });

        Schema::create('inspeksi_k3l', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('petugas_id');
            $table->unsignedInteger('lokasi_id');
            $table->unsignedInteger('master_checklist_id')->comment('[7] Checklist yang digunakan pada sesi inspeksi ini');
            $table->dateTime('tanggal_inspeksi');
            $table->integer('total_item')->comment('[7] Jumlah item checklist aktif saat inspeksi dilakukan');
            $table->integer('item_sesuai')->default(0)->comment('[7] AUTO-CALCULATED: jumlah item ditandai Ya');
            $table->integer('item_tidak_sesuai')->default(0)->comment('[7] AUTO-CALCULATED: jumlah item ditandai Tidak');
            $table->decimal('persentase_kepatuhan', 5, 2)->nullable()->comment('[7] AUTO-CALCULATED: (item_sesuai / total_item) x 100');
            $this->timestamps($table);

            $table->index('petugas_id');
            $table->index('lokasi_id');
            $table->index('master_checklist_id');
            $table->index('tanggal_inspeksi');
        });

        Schema::create('detail_inspeksi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('inspeksi_k3l_id');
            $table->unsignedInteger('item_checklist_id');
            $table->enum('status', ['Ya', 'Tidak'])->comment('Ya = memenuhi standar, Tidak = tidak memenuhi');
            $table->text('analisa_risiko')->nullable()->comment('Wajib diisi jika status = Tidak');
            $table->text('rekomendasi')->nullable()->comment('Wajib diisi jika status = Tidak');
            $table->string('foto_path', 255)->nullable()->comment('Wajib diisi jika status = Tidak');
            $table->text('catatan')->nullable()->comment('Catatan tambahan petugas (opsional)');
            $table->tinyInteger('skor_risiko_hasil')->nullable()->comment('Diambil dari item_checklist.skor_risiko jika Tidak, NULL jika Ya');
            $table->enum('level_risiko_hasil', ['Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'])->nullable()->comment('Diambil dari item_checklist.level_risiko jika Tidak, NULL jika Ya');
            $this->timestamps($table);

            $table->index('inspeksi_k3l_id');
            $table->index('item_checklist_id');
            $table->index('status');
            $table->index('level_risiko_hasil');
        });

        Schema::create('pemeriksaan_apar', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('petugas_id');
            $table->unsignedInteger('apar_id');
            $table->dateTime('tanggal_pemeriksaan');
            $table->text('kondisi_tabung');
            $table->enum('kondisi_segel', ['Tersegel', 'Tidak Tersegel']);
            $table->date('tanggal_expired_update')->nullable()->comment('Jika petugas update tanggal expired saat inspeksi');
            $table->text('catatan')->nullable();
            $this->timestamps($table);

            $table->index('petugas_id');
            $table->index('apar_id');
            $table->index('tanggal_pemeriksaan');
        });

        Schema::create('laporan_insiden', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('satpam_id');
            $table->unsignedInteger('lokasi_id')->nullable();
            $table->string('lokasi_manual', 200)->nullable()->comment('Jika lokasi tidak ada di master data');
            $table->enum('jenis_insiden', ['Kebakaran', 'Kecelakaan Kerja', 'Bencana Alam', 'Gangguan Keamanan']);
            $table->dateTime('tanggal_waktu');
            $table->text('kronologi');
            $table->text('korban')->nullable()->comment('JSON list korban atau nama tunggal (legacy)');
            $table->string('foto_path', 255)->nullable();
            $this->timestamps($table);

            $table->index('satpam_id');
            $table->index('lokasi_id');
            $table->index('jenis_insiden');
            $table->index('tanggal_waktu');
        });

        Schema::create('tindak_lanjut_inspeksi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('detail_inspeksi_id')->unique()->comment('Tindak lanjut per item temuan');
            $table->unsignedInteger('petugas_id')->nullable()->comment('[2] Diganti dari admin_id — ditangani Petugas K3LH');
            $table->dateTime('tanggal_tindakan')->nullable();
            $table->enum('status_perbaikan', ['Dalam Proses', 'Selesai'])->default('Dalam Proses');
            $table->string('foto_bukti_path', 255)->nullable();
            $table->text('catatan_perbaikan')->nullable();
            $this->timestamps($table);

            $table->index('petugas_id');
            $table->index('status_perbaikan');
        });

        Schema::create('tindak_lanjut_insiden', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('laporan_insiden_id')->unique();
            $table->unsignedInteger('petugas_id')->nullable()->comment('[3] Diganti dari admin_id — ditangani Petugas K3LH');
            $table->dateTime('tanggal_tindakan')->nullable();
            $table->enum('status_perbaikan', ['Dalam Proses', 'Selesai'])->default('Dalam Proses');
            $table->string('foto_bukti_path', 255)->nullable();
            $table->text('catatan_perbaikan')->nullable();
            $this->timestamps($table);

            $table->index('petugas_id');
            $table->index('status_perbaikan');
        });

        Schema::create('laporan_ipam', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('titik_ipam_id');
            $table->unsignedInteger('petugas_id');
            $table->integer('bulan')->comment('1-12');
            $table->integer('tahun');
            $table->text('kesimpulan')->nullable()->comment('Kesimpulan keseluruhan bulan');
            $this->timestamps($table);

            $table->unique(['titik_ipam_id', 'bulan', 'tahun']);
            $table->index('petugas_id');
        });

        Schema::create('detail_ipam_mingguan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('laporan_ipam_id');
            $table->integer('minggu_ke')->comment('1-5');
            $table->decimal('suhu_celcius', 5, 2)->nullable();
            $table->decimal('ph', 4, 2)->nullable();
            $table->string('alt_cfu_ml', 100)->nullable()->comment('Angka Lempeng Total (cfu/ml), contoh: 5,50 x 10²');
            $table->enum('salmonella', ['Negatif', 'Positif'])->nullable();
            $table->enum('status', ['Baik', 'Tidak Baik'])->nullable();
            $table->text('kendala')->nullable();
            $table->text('rekomendasi')->nullable();
            $this->timestamps($table);

            $table->index('laporan_ipam_id');
            $table->index('minggu_ke');
        });

        Schema::create('laporan_ipal', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('petugas_id');
            $table->integer('triwulan')->comment('1-4 (I, II, III, IV)');
            $table->integer('tahun');
            $table->text('evaluasi_kinerja')->nullable()->comment('Evaluasi kinerja IPAL selama satu periode triwulan');
            $this->timestamps($table);

            $table->unique(['triwulan', 'tahun']);
            $table->index('petugas_id');
        });

        Schema::create('detail_ipal_harian', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('laporan_ipal_id');
            $table->integer('bulan')->comment('1-12');
            $table->date('tanggal_sampling');
            $table->decimal('debit_input_m3', 10, 2)->comment('Volume air limbah masuk (m³/hari)');
            $table->decimal('debit_output_m3', 10, 2)->comment('Volume air limbah keluar (m³/hari)');
            $table->decimal('ph', 4, 2)->nullable();
            $table->decimal('suhu_celcius', 5, 2)->nullable();
            $this->timestamps($table);

            $table->index('laporan_ipal_id');
            $table->index('tanggal_sampling');
        });

        Schema::create('dampak_lingkungan_ipal', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('laporan_ipal_id');
            $table->string('jenis_dampak', 200)->comment('Contoh: Penurunan kualitas air permukaan');
            $table->text('sumber_dampak');
            $table->text('parameter_pemantauan')->comment('pH, BOD, COD, TSS, TDS, NH3, dll');
            $table->text('tolak_ukur')->comment('Standar baku mutu (PermenLH)');
            $table->string('lokasi_pengelolaan', 200)->nullable();
            $table->text('evaluasi_hasil')->nullable();
            $table->text('tindakan_perbaikan')->nullable();
            $this->timestamps($table);

            $table->index('laporan_ipal_id');
        });

        Schema::create('laporan_limbah_b3', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('petugas_id')->comment('Petugas K3LH yang menginput');
            $table->integer('semester')->comment('1 atau 2 (I atau II)');
            $table->integer('tahun');
            $this->timestamps($table);

            $table->unique(['semester', 'tahun']);
            $table->index('petugas_id');
        });

        Schema::create('jenis_limbah_b3', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('laporan_limbah_b3_id');
            $table->string('nama_limbah', 200)->comment('Contoh: Oli Bekas, Baterai');
            $table->string('kode_limbah', 50)->comment('Contoh: A337-1, B104d');
            $table->string('sumber_limbah', 200);
            $table->string('karakteristik', 100)->comment('Infeksius, Beracun, Korosif, dll');
            $table->string('pengemasan', 100)->comment('Drum/Tong, Jerigen, dll');
            $table->integer('masa_simpan_hari');
            $this->timestamps($table);

            $table->index('laporan_limbah_b3_id');
            $table->index('kode_limbah');
        });

        Schema::create('logbook_limbah_b3', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('laporan_limbah_b3_id');
            $table->integer('bulan')->comment('1-12');
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable()->comment('NULL jika belum keluar');
            $table->string('jenis_limbah', 200);
            $table->string('sumber_limbah', 200);
            $table->decimal('jumlah_masuk_kg', 10, 2);
            $table->decimal('jumlah_keluar_kg', 10, 2)->nullable()->comment('NULL jika belum keluar');
            $table->string('pengemasan', 100)->nullable();
            $this->timestamps($table);

            $table->index('laporan_limbah_b3_id');
            $table->index('tanggal_masuk');
        });

        Schema::create('manifest_limbah_b3', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('laporan_limbah_b3_id');
            $table->string('nomor_manifest', 100)->unique();
            $table->date('tanggal_manifest');
            $table->string('nama_pengirim', 200);
            $table->text('alamat_pengirim');
            $table->string('nama_fasilitas_penyimpanan', 200)->nullable();
            $table->string('penanggung_jawab_pengirim', 100)->nullable();
            $table->string('jabatan_pj_pengirim', 100)->nullable();
            $table->string('kode_limbah', 50);
            $table->string('nama_limbah', 200);
            $table->string('nama_teknik', 200)->nullable();
            $table->date('periode_limbah_mulai')->nullable();
            $table->date('periode_limbah_selesai')->nullable();
            $table->string('karakteristik_limbah', 100);
            $table->string('jenis_kemasan', 100);
            $table->integer('jumlah_kemasan');
            $table->decimal('jumlah_limbah_ton', 10, 3);
            $table->text('keterangan_tambahan')->nullable();
            $table->string('tujuan_pengangkutan', 200);
            $table->string('nama_pengangkut', 200);
            $table->text('alamat_pengangkut');
            $table->string('no_telepon_darurat', 20)->nullable();
            $table->integer('jumlah_ril')->nullable()->comment('Jumlah ritase');
            $table->string('identitas_alat_angkut', 50)->nullable()->comment('Nomor polisi kendaraan');
            $table->dateTime('waktu_mulai_pengangkutan')->nullable();
            $table->dateTime('waktu_selesai_pengangkutan')->nullable();
            $table->string('penanggung_jawab_pengangkut', 100)->nullable();
            $table->string('jabatan_pj_pengangkut', 100)->nullable();
            $table->string('nama_penerima', 200);
            $table->text('alamat_penerima');
            $table->string('no_telepon_penerima', 20)->nullable();
            $table->string('jenis_pengelolaan', 200);
            $table->decimal('jumlah_diterima_kg', 10, 2)->nullable();
            $table->string('penanggung_jawab_penerima', 100)->nullable();
            $table->string('jabatan_pj_penerima', 100)->nullable();
            $this->timestamps($table);

            $table->index('laporan_limbah_b3_id');
            $table->index('nomor_manifest');
            $table->index('tanggal_manifest');
        });

        Schema::create('evaluasi_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kalab_id');
            $table->unsignedInteger('lokasi_id');
            $table->string('unit_laboratorium', 100);
            $table->date('tanggal_evaluasi');
            $table->decimal('persentase_kepatuhan', 5, 2)->nullable()->comment('AUTO-CALCULATED');
            $table->text('catatan_evaluasi')->nullable();
            $this->timestamps($table);

            $table->index('kalab_id');
            $table->index('unit_laboratorium');
        });

        Schema::create('kriteria_audit_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nomor_kriteria', 20);
            $table->text('uraian_kriteria');
            $table->boolean('is_active')->default(true);
            $this->timestamps($table);

            $table->index('nomor_kriteria');
        });

        Schema::create('detail_evaluasi_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('evaluasi_sop_id');
            $table->unsignedInteger('kriteria_audit_id');
            $table->enum('penilaian', ['Sesuai', 'Kritikal', 'Major', 'Minor'])->nullable()->comment('NULL jika tidak ada temuan');
            $table->text('catatan_detail')->nullable();
            $this->timestamps($table);

            $table->index('evaluasi_sop_id');
            $table->index('kriteria_audit_id');
        });

        Schema::create('validasi_evaluasi_sop', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('evaluasi_sop_id')->unique();
            $table->unsignedInteger('petugas_k3lh_id');
            $table->dateTime('tanggal_validasi');
            $table->text('komentar_validasi')->nullable();
            $table->boolean('is_approved')->default(false);
            $this->timestamps($table);

            $table->index('petugas_k3lh_id');
        });

        Schema::create('laporan_generated', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('User yang generate laporan');
            $table->enum('jenis_laporan', ['K3L', 'Insiden', 'IPAM', 'IPAL', 'Limbah B3', 'Evaluasi SOP', 'Inventaris APAR']);
            $table->string('periode', 50)->comment('Format: "Januari 2025", "Triwulan I 2025", dll');
            $table->string('file_path_docx', 255)->nullable()->comment('Path file Word — phpoffice/phpword');
            $table->string('file_path_xlsx', 255)->nullable()->comment('Path file Excel — avadim/fast-excel-templator');
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('jenis_laporan');
            $table->index('created_at');
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->enum('jenis_notifikasi', ['Early Warning APAR', 'Laporan Insiden', 'Temuan Risiko Tinggi', 'Lainnya']);
            $table->string('judul', 200);
            $table->text('pesan');
            $table->integer('reference_id')->nullable()->comment('ID referensi: apar.id, detail_inspeksi.id, laporan_insiden.id, dll');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('is_read');
            $table->index('created_at');
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('action', 100)->comment('CREATE, UPDATE, DELETE, LOGIN, dll');
            $table->string('table_name', 100);
            $table->integer('record_id')->nullable();
            $table->text('old_value')->nullable()->comment('JSON format');
            $table->text('new_value')->nullable()->comment('JSON format');
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('table_name');
            $table->index('created_at');
        });

        $this->addForeignKeys();
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('laporan_generated');
        Schema::dropIfExists('validasi_evaluasi_sop');
        Schema::dropIfExists('detail_evaluasi_sop');
        Schema::dropIfExists('kriteria_audit_sop');
        Schema::dropIfExists('evaluasi_sop');
        Schema::dropIfExists('manifest_limbah_b3');
        Schema::dropIfExists('logbook_limbah_b3');
        Schema::dropIfExists('jenis_limbah_b3');
        Schema::dropIfExists('laporan_limbah_b3');
        Schema::dropIfExists('dampak_lingkungan_ipal');
        Schema::dropIfExists('detail_ipal_harian');
        Schema::dropIfExists('laporan_ipal');
        Schema::dropIfExists('detail_ipam_mingguan');
        Schema::dropIfExists('laporan_ipam');
        Schema::dropIfExists('tindak_lanjut_insiden');
        Schema::dropIfExists('tindak_lanjut_inspeksi');
        Schema::dropIfExists('laporan_insiden');
        Schema::dropIfExists('pemeriksaan_apar');
        Schema::dropIfExists('detail_inspeksi');
        Schema::dropIfExists('inspeksi_k3l');
        Schema::dropIfExists('item_checklist');
        Schema::dropIfExists('master_checklist');
        Schema::dropIfExists('titik_ipam');
        Schema::dropIfExists('unit_ipam');
        Schema::dropIfExists('apar');
        Schema::dropIfExists('users');
        Schema::dropIfExists('lokasi');
    }

    private function timestamps(Blueprint $table): void
    {
        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('updated_at')->nullable();
    }

    private function addForeignKeys(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('lokasi_id')->references('id')->on('lokasi');
        });

        Schema::table('apar', function (Blueprint $table) {
            $table->foreign('lokasi_id')->references('id')->on('lokasi');
        });

        Schema::table('titik_ipam', function (Blueprint $table) {
            $table->foreign('unit_ipam_id')->references('id')->on('unit_ipam');
        });

        Schema::table('master_checklist', function (Blueprint $table) {
            $table->foreign('lokasi_id')->references('id')->on('lokasi');
            $table->foreign('dibuat_oleh_id')->references('id')->on('users');
        });

        Schema::table('item_checklist', function (Blueprint $table) {
            $table->foreign('master_checklist_id')->references('id')->on('master_checklist');
        });

        Schema::table('inspeksi_k3l', function (Blueprint $table) {
            $table->foreign('petugas_id')->references('id')->on('users');
            $table->foreign('lokasi_id')->references('id')->on('lokasi');
            $table->foreign('master_checklist_id')->references('id')->on('master_checklist');
        });

        Schema::table('detail_inspeksi', function (Blueprint $table) {
            $table->foreign('inspeksi_k3l_id')->references('id')->on('inspeksi_k3l');
            $table->foreign('item_checklist_id')->references('id')->on('item_checklist');
        });

        Schema::table('pemeriksaan_apar', function (Blueprint $table) {
            $table->foreign('petugas_id')->references('id')->on('users');
            $table->foreign('apar_id')->references('id')->on('apar');
        });

        Schema::table('laporan_insiden', function (Blueprint $table) {
            $table->foreign('satpam_id')->references('id')->on('users');
            $table->foreign('lokasi_id')->references('id')->on('lokasi');
        });

        Schema::table('tindak_lanjut_inspeksi', function (Blueprint $table) {
            $table->foreign('detail_inspeksi_id')->references('id')->on('detail_inspeksi');
            $table->foreign('petugas_id')->references('id')->on('users');
        });

        Schema::table('tindak_lanjut_insiden', function (Blueprint $table) {
            $table->foreign('laporan_insiden_id')->references('id')->on('laporan_insiden');
            $table->foreign('petugas_id')->references('id')->on('users');
        });

        Schema::table('laporan_ipam', function (Blueprint $table) {
            $table->foreign('titik_ipam_id')->references('id')->on('titik_ipam');
            $table->foreign('petugas_id')->references('id')->on('users');
        });

        Schema::table('detail_ipam_mingguan', function (Blueprint $table) {
            $table->foreign('laporan_ipam_id')->references('id')->on('laporan_ipam');
        });

        Schema::table('laporan_ipal', function (Blueprint $table) {
            $table->foreign('petugas_id')->references('id')->on('users');
        });

        Schema::table('detail_ipal_harian', function (Blueprint $table) {
            $table->foreign('laporan_ipal_id')->references('id')->on('laporan_ipal');
        });

        Schema::table('dampak_lingkungan_ipal', function (Blueprint $table) {
            $table->foreign('laporan_ipal_id')->references('id')->on('laporan_ipal');
        });

        Schema::table('laporan_limbah_b3', function (Blueprint $table) {
            $table->foreign('petugas_id')->references('id')->on('users');
        });

        Schema::table('jenis_limbah_b3', function (Blueprint $table) {
            $table->foreign('laporan_limbah_b3_id')->references('id')->on('laporan_limbah_b3');
        });

        Schema::table('logbook_limbah_b3', function (Blueprint $table) {
            $table->foreign('laporan_limbah_b3_id')->references('id')->on('laporan_limbah_b3');
        });

        Schema::table('manifest_limbah_b3', function (Blueprint $table) {
            $table->foreign('laporan_limbah_b3_id')->references('id')->on('laporan_limbah_b3');
        });

        Schema::table('evaluasi_sop', function (Blueprint $table) {
            $table->foreign('kalab_id')->references('id')->on('users');
            $table->foreign('lokasi_id')->references('id')->on('lokasi');
        });

        Schema::table('detail_evaluasi_sop', function (Blueprint $table) {
            $table->foreign('evaluasi_sop_id')->references('id')->on('evaluasi_sop');
            $table->foreign('kriteria_audit_id')->references('id')->on('kriteria_audit_sop');
        });

        Schema::table('validasi_evaluasi_sop', function (Blueprint $table) {
            $table->foreign('evaluasi_sop_id')->references('id')->on('evaluasi_sop');
            $table->foreign('petugas_k3lh_id')->references('id')->on('users');
        });

        Schema::table('laporan_generated', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('notifikasi', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
