<?php

namespace App\Models;

use Database\Factories\UnitIpamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitIpam extends Model
{
    /** @use HasFactory<UnitIpamFactory> */
    use HasFactory;

    protected $table = 'unit_ipam';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode_unit',
        'nama_unit',
        'deskripsi',
    ];

    /**
     * @return HasMany<TitikIpam, $this>
     */
    public function titikIpam(): HasMany
    {
        return $this->hasMany(TitikIpam::class, 'unit_ipam_id');
    }
}
