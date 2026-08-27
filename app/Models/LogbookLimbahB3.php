<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookLimbahB3 extends Model
{
    protected $table = 'logbook_limbah_b3';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_limbah_b3_id',
        'bulan',
        'tanggal_masuk',
        'tanggal_keluar',
        'jenis_limbah',
        'sumber_limbah',
        'jumlah_masuk_kg',
        'jumlah_keluar_kg',
        'pengemasan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
            'jumlah_masuk_kg' => 'decimal:2',
            'jumlah_keluar_kg' => 'decimal:2',
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
