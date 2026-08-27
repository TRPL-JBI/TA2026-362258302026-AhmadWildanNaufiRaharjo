<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class PhotoStorageService
{
    private const MAX_WIDTH = 1600;

    private const JPEG_QUALITY = 82;

    /**
     * Simpan foto patroli terkompresi (JPEG) ke disk public.
     */
    public function storePatroliPhoto(UploadedFile $file, string $directory): string
    {
        $image = Image::read($file->getRealPath());
        $image->scaleDown(width: self::MAX_WIDTH);

        $filename = Str::uuid()->toString().'.jpg';
        $path = trim($directory, '/').'/'.$filename;

        Storage::disk('public')->put($path, (string) $image->toJpeg(quality: self::JPEG_QUALITY));

        return $path;
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<string>
     */
    public function storePatroliPhotos(array $files, string $directory): array
    {
        $paths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $paths[] = $this->storePatroliPhoto($file, $directory);
            }
        }

        return $paths;
    }

    /**
     * @param  list<string>  $paths
     */
    public function encodePaths(array $paths): ?string
    {
        $paths = array_values(array_filter($paths));

        if ($paths === []) {
            return null;
        }

        return count($paths) === 1 ? $paths[0] : json_encode($paths, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return list<string>
     */
    public function decodePaths(?string $stored): array
    {
        if ($stored === null || trim($stored) === '') {
            return [];
        }

        $stored = trim($stored);
        $decoded = json_decode($stored, true);

        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($path) => is_string($path) && $path !== ''));
        }

        return [$stored];
    }

    public function publicUrl(string $path): string
    {
        return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
    }

    /**
     * Format foto tersimpan untuk Alpine (preview URL, tanpa File).
     *
     * @return list<array{id: string, preview: string, storedPath: string, existing: bool}>
     */
    public function fotoEntriesFromStored(?string $stored): array
    {
        return array_map(function (string $path) {
            return [
                'id' => 'stored-'.md5($path),
                'preview' => $this->publicUrl($path),
                'storedPath' => $path,
                'existing' => true,
            ];
        }, $this->decodePaths($stored));
    }

    public function deleteStored(?string $stored): void
    {
        foreach ($this->decodePaths($stored) as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
