<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailIpalHarian extends Model
{
    protected $table = 'detail_ipal_harian';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_ipal_id',
        'bulan',
        'tanggal_sampling',
        'debit_input_m3',
        'debit_output_m3',
        'ph',
        'suhu_celcius',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tanggal_sampling' => 'date',
            'debit_input_m3' => 'decimal:2',
            'debit_output_m3' => 'decimal:2',
            'ph' => 'decimal:2',
            'suhu_celcius' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<LaporanIpal, $this>
     */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanIpal::class, 'laporan_ipal_id');
    }
}
