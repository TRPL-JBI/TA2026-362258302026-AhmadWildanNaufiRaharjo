<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManifestLimbahB3 extends Model
{
    protected $table = 'manifest_limbah_b3';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_limbah_b3_id',
        'nomor_manifest',
        'tanggal_manifest',
        'nama_pengirim',
        'alamat_pengirim',
        'nama_fasilitas_penyimpanan',
        'penanggung_jawab_pengirim',
        'jabatan_pj_pengirim',
        'kode_limbah',
        'nama_limbah',
        'nama_teknik',
        'periode_limbah_mulai',
        'periode_limbah_selesai',
        'karakteristik_limbah',
        'jenis_kemasan',
        'jumlah_kemasan',
        'jumlah_limbah_ton',
        'keterangan_tambahan',
        'tujuan_pengangkutan',
        'nama_pengangkut',
        'alamat_pengangkut',
        'no_telepon_darurat',
        'jumlah_ril',
        'identitas_alat_angkut',
        'waktu_mulai_pengangkutan',
        'waktu_selesai_pengangkutan',
        'penanggung_jawab_pengangkut',
        'jabatan_pj_pengangkut',
        'nama_penerima',
        'alamat_penerima',
        'no_telepon_penerima',
        'jenis_pengelolaan',
        'jumlah_diterima_kg',
        'penanggung_jawab_penerima',
        'jabatan_pj_penerima',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_manifest' => 'date',
            'periode_limbah_mulai' => 'date',
            'periode_limbah_selesai' => 'date',
            'jumlah_kemasan' => 'integer',
            'jumlah_limbah_ton' => 'decimal:3',
            'jumlah_ril' => 'integer',
            'waktu_mulai_pengangkutan' => 'datetime',
            'waktu_selesai_pengangkutan' => 'datetime',
            'jumlah_diterima_kg' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<LaporanLimbahB3, $this>
     */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanLimbahB3::class, 'laporan_limbah_b3_id');
    }
}
