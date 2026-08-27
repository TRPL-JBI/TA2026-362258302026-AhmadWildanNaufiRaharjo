<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DampakLingkunganIpal extends Model
{
    protected $table = 'dampak_lingkungan_ipal';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_ipal_id',
        'jenis_dampak',
        'sumber_dampak',
        'parameter_pemantauan',
        'tolak_ukur',
        'lokasi_pengelolaan',
        'evaluasi_hasil',
        'tindakan_perbaikan',
    ];

    /**
     * @return BelongsTo<LaporanIpal, $this>
     */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanIpal::class, 'laporan_ipal_id');
    }
}
