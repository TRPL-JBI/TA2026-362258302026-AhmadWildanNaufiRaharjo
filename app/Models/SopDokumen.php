<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopDokumen extends Model
{
    use HasFactory;
    protected $table = 'sop_dokumen';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judul',
        'deskripsi',
        'file_path',
        'original_filename',
        'uploaded_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
