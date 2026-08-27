<?php

namespace App\Services;

use App\Models\UnitIpam;

class UnitIpamCodeService
{
    public static function generateKodeUnit(): string
    {
        $lastNumber = UnitIpam::query()
            ->where('kode_unit', 'like', 'IPM-%')
            ->pluck('kode_unit')
            ->map(function (string $kode): int {
                if (preg_match('/^IPM-(\d+)$/', $kode, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return sprintf('IPM-%02d', $lastNumber + 1);
    }
}
