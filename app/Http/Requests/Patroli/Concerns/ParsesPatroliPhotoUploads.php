<?php

namespace App\Http\Requests\Patroli\Concerns;

use Illuminate\Http\UploadedFile;

trait ParsesPatroliPhotoUploads
{
    /**
     * @return array<int, list<UploadedFile>>
     */
    protected function fotoAparByAparId(): array
    {
        return $this->indexedUploadedFiles('foto_apar');
    }

    /**
     * @return array<int, UploadedFile>
     */
    protected function fotoItemByChecklistId(): array
    {
        $raw = $this->file('foto_item', []);

        if (! is_array($raw)) {
            return [];
        }

        $mapped = [];

        foreach ($raw as $itemId => $file) {
            $id = (int) $itemId;

            if ($id <= 0) {
                continue;
            }

            if ($file instanceof UploadedFile) {
                $mapped[$id] = $file;

                continue;
            }

            if (is_array($file)) {
                $first = collect($file)->first(fn ($f) => $f instanceof UploadedFile);

                if ($first instanceof UploadedFile) {
                    $mapped[$id] = $first;
                }
            }
        }

        return $mapped;
    }

    /**
     * @return array<int, list<UploadedFile>>
     */
    private function indexedUploadedFiles(string $key): array
    {
        $raw = $this->file($key, []);

        if (! is_array($raw)) {
            return [];
        }

        $mapped = [];

        foreach ($raw as $id => $files) {
            $aparId = (int) $id;

            if ($aparId <= 0) {
                continue;
            }

            if ($files instanceof UploadedFile) {
                $mapped[$aparId] = [$files];

                continue;
            }

            if (is_array($files)) {
                $valid = array_values(array_filter(
                    $files,
                    fn ($f) => $f instanceof UploadedFile && $f->isValid(),
                ));

                if ($valid !== []) {
                    $mapped[$aparId] = $valid;
                }
            }
        }

        return $mapped;
    }
}
