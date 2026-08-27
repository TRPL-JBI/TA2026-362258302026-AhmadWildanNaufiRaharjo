<?php

namespace App\Support;

final class B3Semester
{
    public const STATUS_BERLANGSUNG = 'Berlangsung';

    public const STATUS_SELESAI = 'Selesai';

    /**
     * @var array<int, string>
     */
    private const LABELS = [
        1 => 'Semester I',
        2 => 'Semester II',
    ];

    /**
     * @var array<int, list<string>>
     */
    private const BULAN_PER_SEMESTER = [
        1 => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'],
        2 => ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
    ];

    /**
     * @var array<string, int>
     */
    private const BULAN_TO_NUMBER = [
        'Januari' => 1,
        'Februari' => 2,
        'Maret' => 3,
        'April' => 4,
        'Mei' => 5,
        'Juni' => 6,
        'Juli' => 7,
        'Agustus' => 8,
        'September' => 9,
        'Oktober' => 10,
        'November' => 11,
        'Desember' => 12,
    ];

    /**
     * @return array<int, list<string>>
     */
    public static function semesterToBulanMap(): array
    {
        return self::BULAN_PER_SEMESTER;
    }

    /**
     * @return list<int>
     */
    public static function semesterOptions(): array
    {
        return [1, 2];
    }

    public static function label(int $semester): string
    {
        return self::LABELS[$semester] ?? self::LABELS[1];
    }

    public static function labelWithYear(int $semester, int $tahun): string
    {
        return sprintf('%s %d', self::label($semester), $tahun);
    }

    public static function periodeRentang(int $semester): string
    {
        return $semester === 2 ? 'JULI – DESEMBER' : 'JANUARI – JUNI';
    }

    public static function periodeKey(int $tahun, int $semester): string
    {
        return sprintf('%d-s%d', $tahun, $semester);
    }

    /**
     * @return array{tahun: int, semester: int}|null
     */
    public static function parsePeriodeKey(string $key): ?array
    {
        if (preg_match('/^(\d{4})-s([12])$/', $key, $matches) !== 1) {
            return null;
        }

        return [
            'tahun' => (int) $matches[1],
            'semester' => (int) $matches[2],
        ];
    }

    public static function bulanNumberFromName(string $nama): int
    {
        return self::BULAN_TO_NUMBER[$nama] ?? 0;
    }

    public static function bulanNameFromNumber(int $bulan): string
    {
        $found = array_search($bulan, self::BULAN_TO_NUMBER, true);

        return $found === false ? '' : (string) $found;
    }

    /**
     * @return list<int>
     */
    public static function defaultTahunOptions(): array
    {
        $current = (int) date('Y');

        return [$current, $current - 1, $current - 2, $current - 3];
    }
}
