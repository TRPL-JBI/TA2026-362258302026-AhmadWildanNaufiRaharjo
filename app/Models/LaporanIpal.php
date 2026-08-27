<?php

namespace App\Models;

use Database\Factories\LaporanIpalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LaporanIpal extends Model
{
    /** @use HasFactory<LaporanIpalFactory> */
    use HasFactory;

    protected $table = 'laporan_ipal';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'petugas_id',
        'triwulan',
        'tahun',
        'evaluasi_kinerja',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'triwulan' => 'integer',
            'tahun' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /**
     * @return HasMany<DetailIpalHarian, $this>
     */
    public function detailHarian(): HasMany
    {
        return $this->hasMany(DetailIpalHarian::class, 'laporan_ipal_id');
    }

    /**
     * @return HasOne<DampakLingkunganIpal, $this>
     */
    public function dampakLingkungan(): HasOne
    {
        return $this->hasOne(DampakLingkunganIpal::class, 'laporan_ipal_id');
    }
}
