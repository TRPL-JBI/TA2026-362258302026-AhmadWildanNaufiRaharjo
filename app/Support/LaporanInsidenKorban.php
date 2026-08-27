<?php

namespace App\Support;

final class LaporanInsidenKorban
{
    public const MAX_ITEMS = 20;

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{nama: string, usia: ?string, unit_prodi: ?string, jabatan: ?string, status: ?string}>
     */
    public static function normalizeList(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nama = trim((string) ($item['nama'] ?? $item['korban'] ?? ''));
            if ($nama === '') {
                continue;
            }

            $normalized[] = [
                'nama' => $nama,
                'usia' => self::nullableTrim($item['usia'] ?? $item['usia_korban'] ?? null),
                'unit_prodi' => self::nullableTrim($item['unit_prodi'] ?? null),
                'jabatan' => self::nullableTrim($item['jabatan'] ?? $item['jabatan_korban'] ?? null),
                'status' => self::nullableTrim($item['status'] ?? $item['status_korban'] ?? null),
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param  list<array{nama: string, usia: ?string, unit_prodi: ?string, jabatan: ?string, status: ?string}>  $items
     */
    public static function encode(?array $items): ?string
    {
        if ($items === null || $items === []) {
            return null;
        }

        return json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }

    /**
     * Decode dari kolom korban (JSON baru atau nama tunggal lama).
     *
     * @return list<array{nama: string, usia: ?string, unit_prodi: ?string, jabatan: ?string, status: ?string}>
     */
    public static function decode(
        ?string $korban,
        ?string $usiaKorban = null,
        ?string $unitProdi = null,
        ?string $jabatanKorban = null,
        ?string $statusKorban = null,
    ): array {
        if ($korban === null || trim($korban) === '') {
            return [];
        }

        $trimmed = trim($korban);
        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return self::normalizeList($decoded);
        }

        return [[
            'nama' => $trimmed,
            'usia' => self::nullableTrim($usiaKorban),
            'unit_prodi' => self::nullableTrim($unitProdi),
            'jabatan' => self::nullableTrim($jabatanKorban),
            'status' => self::nullableTrim($statusKorban),
        ]];
    }

    /**
     * Ringkas untuk tampilan teks (tindak lanjut / notifikasi).
     *
     * @param  list<array{nama: string, usia: ?string, unit_prodi: ?string, jabatan: ?string, status: ?string}>  $items
     */
    public static function summary(array $items): string
    {
        if ($items === []) {
            return 'Tidak ada / tidak dilaporkan';
        }

        return collect($items)
            ->map(function (array $item) {
                $parts = [$item['nama']];

                if (! empty($item['usia'])) {
                    $parts[] = $item['usia'].' th';
                }
                if (! empty($item['unit_prodi'])) {
                    $parts[] = $item['unit_prodi'];
                }
                if (! empty($item['jabatan'])) {
                    $parts[] = $item['jabatan'];
                }
                if (! empty($item['status'])) {
                    $parts[] = $item['status'];
                }

                return implode(' · ', $parts);
            })
            ->implode('; ');
    }

    private static function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
