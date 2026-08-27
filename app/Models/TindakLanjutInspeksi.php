<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TindakLanjutInspeksi extends Model
{
    protected $table = 'tindak_lanjut_inspeksi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'detail_inspeksi_id',
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
     * @return BelongsTo<DetailInspeksi, $this>
     */
    public function detailInspeksi(): BelongsTo
    {
        return $this->belongsTo(DetailInspeksi::class, 'detail_inspeksi_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
