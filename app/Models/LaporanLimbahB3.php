<?php

namespace App\Models;

use Database\Factories\LaporanLimbahB3Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanLimbahB3 extends Model
{
    /** @use HasFactory<LaporanLimbahB3Factory> */
    use HasFactory;

    protected $table = 'laporan_limbah_b3';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'petugas_id',
        'semester',
        'tahun',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => 'integer',
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
     * @return HasMany<JenisLimbahB3, $this>
     */
    public function jenisLimbah(): HasMany
    {
        return $this->hasMany(JenisLimbahB3::class, 'laporan_limbah_b3_id');
    }

    /**
     * @return HasMany<LogbookLimbahB3, $this>
     */
    public function logbook(): HasMany
    {
        return $this->hasMany(LogbookLimbahB3::class, 'laporan_limbah_b3_id');
    }

    /**
     * @return HasMany<ManifestLimbahB3, $this>
     */
    public function manifest(): HasMany
    {
        return $this->hasMany(ManifestLimbahB3::class, 'laporan_limbah_b3_id');
    }
}
