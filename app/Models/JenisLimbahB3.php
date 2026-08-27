<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisLimbahB3 extends Model
{
    protected $table = 'jenis_limbah_b3';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_limbah_b3_id',
        'nama_limbah',
        'kode_limbah',
        'sumber_limbah',
        'karakteristik',
        'pengemasan',
        'masa_simpan_hari',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'masa_simpan_hari' => 'integer',
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
