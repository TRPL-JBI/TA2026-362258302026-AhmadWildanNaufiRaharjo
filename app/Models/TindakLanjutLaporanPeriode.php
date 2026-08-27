<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TindakLanjutLaporanPeriode extends Model
{
    public const STATUS_BERLANGSUNG = 'Berlangsung';

    public const STATUS_SELESAI = 'Selesai';

    protected $table = 'tindak_lanjut_laporan_periode';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tahun',
        'caturwulan',
        'status',
        'selesai_by_id',
        'selesai_at',
        'items_snapshot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'caturwulan' => 'integer',
            'selesai_at' => 'datetime',
            'items_snapshot' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function selesaiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selesai_by_id');
    }
}
