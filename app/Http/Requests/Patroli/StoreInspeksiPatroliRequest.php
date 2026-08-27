<?php

namespace App\Http\Requests\Patroli;

use App\Http\Requests\Patroli\Concerns\ParsesPatroliPhotoUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreInspeksiPatroliRequest extends FormRequest
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
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.lokasi_id' => ['sometimes', 'integer', 'exists:lokasi,id'],
            'sections.*.id' => ['sometimes', 'integer', 'exists:lokasi,id'],
            'sections.*.master_checklist_id' => ['required', 'integer', 'exists:master_checklist,id'],
            'sections.*.inspeksi_id' => ['sometimes', 'integer', 'exists:inspeksi_k3l,id'],
            'sections.*.items' => ['required', 'array', 'min:1'],
            'sections.*.items.*.id' => ['sometimes', 'integer', 'exists:item_checklist,id'],
            'sections.*.items.*.item_checklist_id' => ['sometimes', 'integer', 'exists:item_checklist,id'],
            'sections.*.items.*.status' => ['sometimes', 'string', 'in:ya,tidak,belum,Ya,Tidak,belum'],
            'sections.*.items.*.analisa_risiko' => ['nullable', 'string', 'max:5000'],
            'sections.*.items.*.analisaRisiko' => ['nullable', 'string', 'max:5000'],
            'sections.*.items.*.rekomendasi' => ['nullable', 'string', 'max:5000'],
            'sections.*.items.*.catatan' => ['nullable', 'string', 'max:2000'],
            'foto_item' => ['sometimes', 'array'],
            'foto_item.*' => ['sometimes', 'image', 'max:10240'],
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function fotoItemFiles(): array
    {
        return $this->fotoItemByChecklistId();
    }
}
