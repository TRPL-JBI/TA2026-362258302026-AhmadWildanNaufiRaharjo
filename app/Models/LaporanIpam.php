<?php

namespace App\Models;

use Database\Factories\LaporanIpamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanIpam extends Model
{
    /** @use HasFactory<LaporanIpamFactory> */
    use HasFactory;

    protected $table = 'laporan_ipam';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'titik_ipam_id',
        'petugas_id',
        'bulan',
        'tahun',
        'kesimpulan',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TitikIpam, $this>
     */
    public function titikIpam(): BelongsTo
    {
        return $this->belongsTo(TitikIpam::class, 'titik_ipam_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /**
     * @return HasMany<DetailIpamMingguan, $this>
     */
    public function detailMingguan(): HasMany
    {
        return $this->hasMany(DetailIpamMingguan::class, 'laporan_ipam_id');
    }
}
