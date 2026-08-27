<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterChecklist extends Model
{
    protected $table = 'master_checklist';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_checklist',
        'lokasi_id',
        'dibuat_oleh_id',
        'jenis_pengelola',
        'status',
    ];

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }

    /**
     * @return HasMany<ItemChecklist, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItemChecklist::class, 'master_checklist_id');
    }
}
