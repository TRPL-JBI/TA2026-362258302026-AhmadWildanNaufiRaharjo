<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanGenerated extends Model
{
    public const JENIS_K3L = 'K3L';

    public const JENIS_INSIDEN = 'Insiden';

    public const JENIS_INVENTARIS_APAR = 'Inventaris APAR';

    public const JENIS_IPAM = 'IPAM';

    public const JENIS_B3 = 'Limbah B3';

    public const JENIS_IPAL = 'IPAL';

    public const JENIS_TINDAK_LANJUT = 'Tindak Lanjut';

    public const UPDATED_AT = null;

    protected $table = 'laporan_generated';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'jenis_laporan',
        'periode',
        'file_path_docx',
        'file_path_xlsx',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
