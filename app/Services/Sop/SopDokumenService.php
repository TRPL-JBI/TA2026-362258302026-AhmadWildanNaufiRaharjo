<?php

namespace App\Services\Sop;

use App\Models\SopDokumen;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SopDokumenService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForIndex(): array
    {
        return SopDokumen::query()
            ->with('uploader')
            ->orderBy('judul')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (SopDokumen $row) => $this->serialize($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(User $uploader, array $payload, UploadedFile $file): SopDokumen
    {
        return DB::transaction(function () use ($uploader, $payload, $file) {
            $dokumen = SopDokumen::query()->create([
                'judul' => $payload['judul'],
                'deskripsi' => $this->nullableString($payload['deskripsi'] ?? null),
                'file_path' => '',
                'original_filename' => $file->getClientOriginalName(),
                'uploaded_by' => $uploader->id,
            ]);

            $relativePath = $this->storePdf($dokumen->id, $file);
            $dokumen->update(['file_path' => $relativePath]);

            return $dokumen->fresh(['uploader']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(SopDokumen $dokumen, User $uploader, array $payload, ?UploadedFile $file = null): SopDokumen
    {
        return DB::transaction(function () use ($dokumen, $uploader, $payload, $file) {
            $updates = [
                'judul' => $payload['judul'],
                'deskripsi' => $this->nullableString($payload['deskripsi'] ?? null),
                'uploaded_by' => $uploader->id,
            ];

            if ($file instanceof UploadedFile) {
                $this->deleteStoredFile($dokumen->file_path);
                $updates['file_path'] = $this->storePdf($dokumen->id, $file);
                $updates['original_filename'] = $file->getClientOriginalName();
            }

            $dokumen->update($updates);

            return $dokumen->fresh(['uploader']);
        });
    }

    public function destroy(SopDokumen $dokumen): void
    {
        DB::transaction(function () use ($dokumen) {
            $this->deleteStoredFile($dokumen->file_path);
            $dokumen->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(SopDokumen $dokumen): array
    {
        $dokumen->loadMissing('uploader');

        return [
            'id' => $dokumen->id,
            'judul' => $dokumen->judul,
            'deskripsi' => $dokumen->deskripsi ?? '',
            'original_filename' => $dokumen->original_filename,
            'uploader_nama' => $dokumen->uploader?->nama_lengkap ?? '-',
            'uploaded_at' => $dokumen->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '-',
            'preview_url' => route('sop.preview', $dokumen, false),
        ];
    }

    private function storePdf(int $dokumenId, UploadedFile $file): string
    {
        $filename = sprintf('%d-%s.pdf', time(), bin2hex(random_bytes(4)));
        $relativePath = sprintf('sop-dokumen/%d/%s', $dokumenId, $filename);

        Storage::disk('local')->putFileAs(
            dirname($relativePath),
            $file,
            basename($relativePath),
        );

        return $relativePath;
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('local')->delete($path);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
