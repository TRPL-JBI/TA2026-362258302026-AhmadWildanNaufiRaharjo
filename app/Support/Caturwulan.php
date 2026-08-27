<?php

namespace App\Support;

final class Caturwulan
{
    /**
     * @var array<int, string>
     */
    private const KEYS = [
        1 => 'Caturwulan I (Jan - Apr)',
        2 => 'Caturwulan II (Mei - Agu)',
        3 => 'Caturwulan III (Sep - Des)',
    ];

    /**
     * @var array<int, string>
     */
    private const SHORT_LABELS = [
        1 => 'Caturwulan I',
        2 => 'Caturwulan II',
        3 => 'Caturwulan III',
    ];

    /**
     * @var array<int, string>
     */
    private const RENTANG_BULAN = [
        1 => 'Jan - Apr',
        2 => 'Mei - Agu',
        3 => 'Sep - Des',
    ];

    /**
     * @var array<int, list<int>>
     */
    private const BULAN_PER_CATURWULAN = [
        1 => [1, 2, 3, 4],
        2 => [5, 6, 7, 8],
        3 => [9, 10, 11, 12],
    ];

    /**
     * @return list<int>
     */
    public static function caturwulanNumbers(): array
    {
        return [1, 2, 3];
    }

    public static function keyFromNumber(int $caturwulan): string
    {
        return self::KEYS[$caturwulan] ?? self::KEYS[1];
    }

    public static function numberFromMonth(int $month): int
    {
        foreach (self::BULAN_PER_CATURWULAN as $number => $months) {
            if (in_array($month, $months, true)) {
                return $number;
            }
        }

        return 1;
    }

    /**
     * @return list<int>
     */
    public static function bulanNumbersForCaturwulan(int $caturwulan): array
    {
        return self::BULAN_PER_CATURWULAN[$caturwulan] ?? self::BULAN_PER_CATURWULAN[1];
    }

    public static function label(int $caturwulan, int $tahun): string
    {
        return sprintf('%s %d', self::keyFromNumber($caturwulan), $tahun);
    }

    public static function shortLabel(int $caturwulan): string
    {
        return self::SHORT_LABELS[$caturwulan] ?? self::SHORT_LABELS[1];
    }

    public static function rentangBulan(int $caturwulan): string
    {
        return self::RENTANG_BULAN[$caturwulan] ?? self::RENTANG_BULAN[1];
    }
}
