<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DetailInspeksi extends Model
{
    public const STATUS_YA = 'Ya';

    public const STATUS_TIDAK = 'Tidak';

    protected $table = 'detail_inspeksi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inspeksi_k3l_id',
        'item_checklist_id',
        'status',
        'analisa_risiko',
        'rekomendasi',
        'foto_path',
        'catatan',
        'skor_risiko_hasil',
        'level_risiko_hasil',
    ];

    /**
     * @return BelongsTo<InspeksiK3l, $this>
     */
    public function inspeksi(): BelongsTo
    {
        return $this->belongsTo(InspeksiK3l::class, 'inspeksi_k3l_id');
    }

    /**
     * @return BelongsTo<ItemChecklist, $this>
     */
    public function itemChecklist(): BelongsTo
    {
        return $this->belongsTo(ItemChecklist::class, 'item_checklist_id');
    }

    /**
     * @return HasOne<TindakLanjutInspeksi, $this>
     */
    public function tindakLanjut(): HasOne
    {
        return $this->hasOne(TindakLanjutInspeksi::class, 'detail_inspeksi_id');
    }

    public function isTemuanKritis(): bool
    {
        return $this->status === self::STATUS_TIDAK
            && in_array($this->level_risiko_hasil, ['Tinggi', 'Sangat Tinggi'], true);
    }
}
