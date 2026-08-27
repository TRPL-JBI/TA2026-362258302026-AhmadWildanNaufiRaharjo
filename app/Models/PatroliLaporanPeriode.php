<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatroliLaporanPeriode extends Model
{
    public const STATUS_BERLANGSUNG = 'Berlangsung';

    public const STATUS_SELESAI = 'Selesai';

    public const JENIS_TEMUAN = 'temuan';

    public const JENIS_APAR = 'apar';

    protected $table = 'patroli_laporan_periode';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'petugas_id',
        'tahun',
        'caturwulan',
        'jenis',
        'status',
        'selesai_at',
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
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
