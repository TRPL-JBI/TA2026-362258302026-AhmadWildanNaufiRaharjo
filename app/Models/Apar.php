<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AparFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apar extends Model
{
    /** @use HasFactory<AparFactory> */
    use HasFactory;

    public const KONDISI_BAIK_TERSEGEL = 'Baik Tersegel';

    public const KONDISI_TERBUKA = 'Terbuka';

    protected $table = 'apar';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lokasi_id',
        'kode_apar',
        'jenis_apar',
        'kapasitas_kg',
        'tanggal_expired',
        'status_kondisi',
        'keterangan',
        'qr_code_path',
        'is_notified',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kapasitas_kg' => 'decimal:2',
            'tanggal_expired' => 'date',
            'is_notified' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    /**
     * @return HasMany<PemeriksaanApar, $this>
     */
    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(PemeriksaanApar::class, 'apar_id');
    }

    /**
     * Status masa berlaku untuk highlight di tabel inventaris.
     *
     * @return 'expired'|'warning'|'normal'
     */
    public function expiredStatus(?Carbon $today = null): string
    {
        $today ??= Carbon::today();
        $expired = $this->tanggal_expired;

        if ($expired->lt($today)) {
            return 'expired';
        }

        if ($expired->lte($today->copy()->addDays(30))) {
            return 'warning';
        }

        return 'normal';
    }

    public function jenisKapasitasLabel(): string
    {
        $kg = rtrim(rtrim(number_format((float) $this->kapasitas_kg, 2, '.', ''), '0'), '.');

        return "{$this->jenis_apar} - {$kg} Kg";
    }

    /**
     * @return list<string>
     */
    public static function kondisiOptions(): array
    {
        return [
            self::KONDISI_BAIK_TERSEGEL,
            self::KONDISI_TERBUKA,
        ];
    }

    /**
     * @return array{class: string, label: string}|null
     */
    public function kondisiBadge(): ?array
    {
        return match ($this->status_kondisi) {
            self::KONDISI_BAIK_TERSEGEL => [
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'label' => self::KONDISI_BAIK_TERSEGEL,
            ],
            self::KONDISI_TERBUKA => [
                'class' => 'bg-amber-100 text-amber-800 border-amber-200',
                'label' => self::KONDISI_TERBUKA,
            ],
            default => null,
        };
    }

    /**
     * Map nilai kondisi segel dari form patroli ke status_kondisi inventaris.
     */
    public static function statusKondisiFromSegel(string $kondisiSegel): string
    {
        return match ($kondisiSegel) {
            'tersegel', 'Tersegel' => self::KONDISI_BAIK_TERSEGEL,
            'tidak-tersegel', 'Tidak Tersegel' => self::KONDISI_TERBUKA,
            default => self::KONDISI_TERBUKA,
        };
    }
}
