<?php

namespace App\Models;

use Database\Factories\DetailIpamMingguanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailIpamMingguan extends Model
{
    /** @use HasFactory<DetailIpamMingguanFactory> */
    use HasFactory;

    protected $table = 'detail_ipam_mingguan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_ipam_id',
        'minggu_ke',
        'suhu_celcius',
        'ph',
        'alt_cfu_ml',
        'salmonella',
        'status',
        'kendala',
        'rekomendasi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minggu_ke' => 'integer',
            'suhu_celcius' => 'decimal:2',
            'ph' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<LaporanIpam, $this>
     */
    public function laporanIpam(): BelongsTo
    {
        return $this->belongsTo(LaporanIpam::class, 'laporan_ipam_id');
    }
}
