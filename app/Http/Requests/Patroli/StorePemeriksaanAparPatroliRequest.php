<?php

namespace App\Http\Requests\Patroli;

use App\Http\Requests\Patroli\Concerns\ParsesPatroliPhotoUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StorePemeriksaanAparPatroliRequest extends FormRequest
{
    use ParsesPatroliPhotoUploads;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('Petugas K3LH') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pemeriksaan' => ['sometimes', 'array'],
            'pemeriksaan.*.apar_id' => ['sometimes', 'integer', 'exists:apar,id'],
            'pemeriksaan.*.pemeriksaan_id' => ['sometimes', 'integer', 'exists:pemeriksaan_apar,id'],
            'pemeriksaan.*.id' => ['sometimes', 'integer', 'exists:apar,id'],
            'pemeriksaan.*.kondisi_tabung' => ['nullable', 'string', 'max:5000'],
            'pemeriksaan.*.kondisiTabung' => ['nullable', 'string', 'max:5000'],
            'pemeriksaan.*.kondisi_segel' => ['nullable', 'string'],
            'pemeriksaan.*.kondisiSegel' => ['nullable', 'string'],
            'pemeriksaan.*.tanggal_expired_update' => ['nullable', 'date'],
            'pemeriksaan.*.tanggalExpiredUpdate' => ['nullable', 'date'],
            'pemeriksaan.*.catatan' => ['nullable', 'string', 'max:2000'],
            'foto_apar' => ['sometimes', 'array'],
            'foto_apar.*' => ['sometimes'],
            'foto_apar.*.*' => ['image', 'max:10240'],
        ];
    }

    /**
     * @return array<int, list<UploadedFile>>
     */
    public function fotoAparFiles(): array
    {
        return $this->fotoAparByAparId();
    }
}
