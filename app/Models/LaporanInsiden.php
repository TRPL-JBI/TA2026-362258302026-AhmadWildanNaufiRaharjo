<?php

namespace App\Models;

use Database\Factories\LaporanInsidenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LaporanInsiden extends Model
{
    /** @use HasFactory<LaporanInsidenFactory> */
    use HasFactory;

    protected $table = 'laporan_insiden';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'satpam_id',
        'lokasi_id',
        'lokasi_manual',
        'jenis_insiden',
        'tanggal_waktu',
        'kronologi',
        'korban',
        'usia_korban',
        'unit_prodi',
        'jabatan_korban',
        'status_korban',
        'foto_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_waktu' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function satpam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'satpam_id');
    }

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    /**
     * @return HasOne<TindakLanjutInsiden, $this>
     */
    public function tindakLanjut(): HasOne
    {
        return $this->hasOne(TindakLanjutInsiden::class, 'laporan_insiden_id');
    }
}
