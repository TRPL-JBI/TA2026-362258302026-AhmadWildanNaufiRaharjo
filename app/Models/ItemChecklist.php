<?php

namespace App\Models;

use App\Support\ChecklistRisk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemChecklist extends Model
{
    protected $table = 'item_checklist';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'master_checklist_id',
        'nama_item',
        'deskripsi',
        'probability',
        'severity',
        'skor_risiko',
        'level_risiko',
        'urutan',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (ItemChecklist $item) {
            $score = (int) $item->probability * (int) $item->severity;
            $item->skor_risiko = $score;
            $item->level_risiko = ChecklistRisk::levelFromScore($score);
        });
    }

    /**
     * @return BelongsTo<MasterChecklist, $this>
     */
    public function masterChecklist(): BelongsTo
    {
        return $this->belongsTo(MasterChecklist::class, 'master_checklist_id');
    }
}
