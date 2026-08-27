<?php

namespace App\Models;

use Database\Factories\TitikIpamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitikIpam extends Model
{
    /** @use HasFactory<TitikIpamFactory> */
    use HasFactory;

    protected $table = 'titik_ipam';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'unit_ipam_id',
        'titik_lokasi',
        'deskripsi',
    ];

    /**
     * @return BelongsTo<UnitIpam, $this>
     */
    public function unitIpam(): BelongsTo
    {
        return $this->belongsTo(UnitIpam::class, 'unit_ipam_id');
    }
}
