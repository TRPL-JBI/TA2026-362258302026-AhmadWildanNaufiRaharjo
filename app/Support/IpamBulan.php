<?php

namespace App\Support;

final class IpamBulan
{
    public const STATUS_BERLANGSUNG = 'Berlangsung';

    public const STATUS_SELESAI = 'Selesai';

    public const MAX_MINGGU = 4;

    /**
     * @var list<string>
     */
    public const BULAN_NAMES = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
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
     * @return list<string>
     */
    public static function bulanOptions(): array
    {
        return self::BULAN_NAMES;
    }

    /**
     * @return list<int>
     */
    public static function defaultTahunOptions(): array
    {
        $current = (int) date('Y');

        return [$current, $current - 1, $current - 2, $current - 3];
    }

    public static function periodeKey(int $tahun, int $bulan): string
    {
        return sprintf('%d-%d', $tahun, $bulan);
    }

    /**
     * @return array{tahun: int, bulan: int}|null
     */
    public static function parsePeriodeKey(string $key): ?array
    {
        if (! preg_match('/^(\d{4})-(\d{1,2})$/', $key, $matches)) {
            return null;
        }

        $tahun = (int) $matches[1];
        $bulan = (int) $matches[2];

        if ($bulan < 1 || $bulan > 12) {
            return null;
        }

        return ['tahun' => $tahun, 'bulan' => $bulan];
    }
}
