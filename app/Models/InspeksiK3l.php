<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspeksiK3l extends Model
{
    protected $table = 'inspeksi_k3l';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'petugas_id',
        'lokasi_id',
        'master_checklist_id',
        'tanggal_inspeksi',
        'total_item',
        'item_sesuai',
        'item_tidak_sesuai',
        'persentase_kepatuhan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_inspeksi' => 'datetime',
            'persentase_kepatuhan' => 'decimal:2',
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
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    /**
     * @return BelongsTo<MasterChecklist, $this>
     */
    public function masterChecklist(): BelongsTo
    {
        return $this->belongsTo(MasterChecklist::class, 'master_checklist_id');
    }

    /**
     * @return HasMany<DetailInspeksi, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(DetailInspeksi::class, 'inspeksi_k3l_id');
    }
}
