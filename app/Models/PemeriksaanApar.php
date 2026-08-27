<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemeriksaanApar extends Model
{
    protected $table = 'pemeriksaan_apar';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'petugas_id',
        'apar_id',
        'tanggal_pemeriksaan',
        'kondisi_tabung',
        'kondisi_segel',
        'tanggal_expired_update',
        'catatan',
        'foto_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_pemeriksaan' => 'datetime',
            'tanggal_expired_update' => 'date',
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
     * @return BelongsTo<Apar, $this>
     */
    public function apar(): BelongsTo
    {
        return $this->belongsTo(Apar::class, 'apar_id');
    }

    public static function segelFromForm(string $value): string
    {
        return match ($value) {
            'tersegel', 'Tersegel' => 'Tersegel',
            'tidak-tersegel', 'Tidak Tersegel' => 'Tidak Tersegel',
            default => 'Tidak Tersegel',
        };
    }
}
