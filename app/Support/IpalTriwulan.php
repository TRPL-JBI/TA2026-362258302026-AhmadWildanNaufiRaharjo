<?php

namespace App\Support;

final class IpalTriwulan
{
    public const STATUS_BERLANGSUNG = 'Berlangsung';

    public const STATUS_SELESAI = 'Selesai';

    /**
     * @var array<int, string>
     */
    private const KEYS = [
        1 => 'Triwulan I (Jan - Mar)',
        2 => 'Triwulan II (Apr - Jun)',
        3 => 'Triwulan III (Jul - Sep)',
        4 => 'Triwulan IV (Okt - Des)',
    ];

    /**
     * @var array<int, list<string>>
     */
    private const BULAN_PER_TRIWULAN = [
        1 => ['Januari', 'Februari', 'Maret'],
        2 => ['April', 'Mei', 'Juni'],
        3 => ['Juli', 'Agustus', 'September'],
        4 => ['Oktober', 'November', 'Desember'],
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
     * @return array<string, list<string>>
     */
    public static function triwulanToBulanMap(): array
    {
        $map = [];
        foreach (self::KEYS as $number => $key) {
            $map[$key] = self::BULAN_PER_TRIWULAN[$number];
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public static function triwulanKeys(): array
    {
        return array_values(self::KEYS);
    }

    public static function keyFromNumber(int $triwulan): string
    {
        return self::KEYS[$triwulan] ?? self::KEYS[1];
    }

    public static function numberFromKey(string $key): ?int
    {
        $found = array_search($key, self::KEYS, true);

        return $found === false ? null : (int) $found;
    }

    /**
     * @return list<int>
     */
    public static function bulanNumbersForTriwulan(int $triwulan): array
    {
        $names = self::BULAN_PER_TRIWULAN[$triwulan] ?? [];

        return array_values(array_map(
            fn (string $nama) => self::bulanNumberFromName($nama),
            $names,
        ));
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

    public static function label(int $triwulan, int $tahun): string
    {
        return sprintf('%s %d', self::keyFromNumber($triwulan), $tahun);
    }

    public static function romanLabel(int $triwulan): string
    {
        return match ($triwulan) {
            2 => 'TRIWULAN II',
            3 => 'TRIWULAN III',
            4 => 'TRIWULAN IV',
            default => 'TRIWULAN I',
        };
    }

    public static function periodeRentang(int $triwulan): string
    {
        return match ($triwulan) {
            2 => 'APRIL – JUNI',
            3 => 'JULI – SEPTEMBER',
            4 => 'OKTOBER – DESEMBER',
            default => 'JANUARI – MARET',
        };
    }

    public static function periodeKey(int $tahun, int $triwulan): string
    {
        return sprintf('%d-q%d', $tahun, $triwulan);
    }

    /**
     * @return array{tahun: int, triwulan: int}|null
     */
    public static function parsePeriodeKey(string $key): ?array
    {
        if (preg_match('/^(\d{4})-q([1-4])$/', $key, $matches) !== 1) {
            return null;
        }

        return [
            'tahun' => (int) $matches[1],
            'triwulan' => (int) $matches[2],
        ];
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
