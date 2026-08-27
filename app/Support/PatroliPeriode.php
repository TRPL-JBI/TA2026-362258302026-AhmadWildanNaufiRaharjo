<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

final class PatroliPeriode
{
    public static function keyFromDate(Carbon $date): string
    {
        $caturwulan = Caturwulan::numberFromMonth((int) $date->month);

        return self::key((int) $date->year, $caturwulan);
    }

    public static function key(int $year, int $caturwulan): string
    {
        return sprintf('%d-%d', $year, $caturwulan);
    }

    public static function isValidKey(string $periode): bool
    {
        return (bool) preg_match('/^\d{4}-[1-3]$/', $periode);
    }

    /**
     * @return array{year: int, caturwulan: int}
     */
    public static function parse(string $periode): array
    {
        if (! self::isValidKey($periode)) {
            throw ValidationException::withMessages([
                'periode' => 'Periode patroli tidak valid.',
            ]);
        }

        [$year, $caturwulan] = array_map('intval', explode('-', $periode, 2));

        return [
            'year' => $year,
            'caturwulan' => $caturwulan,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dateRange(int $year, int $caturwulan): array
    {
        $months = Caturwulan::bulanNumbersForCaturwulan($caturwulan);
        $startMonth = min($months);
        $endMonth = max($months);

        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = Carbon::create($year, $endMonth, 1)->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dateRangeForKey(string $periode): array
    {
        $parsed = self::parse($periode);

        return self::dateRange($parsed['year'], $parsed['caturwulan']);
    }

    public static function label(string $periode): string
    {
        $parsed = self::parse($periode);

        return Caturwulan::label($parsed['caturwulan'], $parsed['year']);
    }

    public static function shortLabel(string $periode): string
    {
        $parsed = self::parse($periode);

        return Caturwulan::shortLabel($parsed['caturwulan']).' '.$parsed['year'];
    }

    public static function rentangBulan(string $periode): string
    {
        $parsed = self::parse($periode);

        return Caturwulan::rentangBulan($parsed['caturwulan']);
    }

    public static function displayLabel(string $periode): string
    {
        $parsed = self::parse($periode);

        return sprintf(
            '%s · %s %d',
            Caturwulan::shortLabel($parsed['caturwulan']),
            Caturwulan::rentangBulan($parsed['caturwulan']),
            $parsed['year'],
        );
    }

    public static function rentangTanggal(string $periode): string
    {
        [$start, $end] = self::dateRangeForKey($periode);

        Carbon::setLocale('id');

        return sprintf(
            '%s s/d %s',
            $start->translatedFormat('j F'),
            $end->translatedFormat('j F Y'),
        );
    }

    public static function laporanPatroliTitle(string $periode): string
    {
        return 'Laporan Patroli K3LH - '.self::label($periode);
    }

    public static function laporanAparTitle(string $periode): string
    {
        return 'Laporan Inventaris APAR - '.self::label($periode);
    }
}
