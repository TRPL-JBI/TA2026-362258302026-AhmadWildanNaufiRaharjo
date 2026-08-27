<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TindakLanjutInsiden extends Model
{
    protected $table = 'tindak_lanjut_insiden';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_insiden_id',
        'petugas_id',
        'tanggal_tindakan',
        'tanggal_selesai',
        'status_perbaikan',
        'foto_bukti_path',
        'catatan_perbaikan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_tindakan' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LaporanInsiden, $this>
     */
    public function laporanInsiden(): BelongsTo
    {
        return $this->belongsTo(LaporanInsiden::class, 'laporan_insiden_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
