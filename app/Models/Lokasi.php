<?php

namespace App\Models;

use Database\Factories\LokasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lokasi extends Model
{
    /** @use HasFactory<LokasiFactory> */
    use HasFactory;

    protected $table = 'lokasi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode_lokasi',
        'nama_lokasi',
        'jenis_lokasi',
        'deskripsi',
        'qr_code_path',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'lokasi_id');
    }

    /**
     * @return HasMany<Apar, $this>
     */
    public function apar(): HasMany
    {
        return $this->hasMany(Apar::class, 'lokasi_id');
    }

    /**
     * @return HasMany<MasterChecklist, $this>
     */
    public function masterChecklists(): HasMany
    {
        return $this->hasMany(MasterChecklist::class, 'lokasi_id');
    }

    /**
     * @return HasMany<InspeksiK3l, $this>
     */
    public function inspeksiK3l(): HasMany
    {
        return $this->hasMany(InspeksiK3l::class, 'lokasi_id');
    }
}
