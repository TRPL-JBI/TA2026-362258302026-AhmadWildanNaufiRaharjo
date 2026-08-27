<?php

namespace App\Support;

final class IpamAltFormat
{
    /**
     * Menerima angka biasa (12,5) atau notasi ilmiah tulisan (5,50 x 10²).
     */
    public static function isValid(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (preg_match('/^\d+([,.]\d+)?$/', $value) === 1) {
            return true;
        }

        return preg_match(
            '/^\d+([,.]\d+)?\s*[x×]\s*10\s*(?:\^?\s*[\d]+|[\d²³¹⁰⁴⁵⁶⁷⁸⁹⁺⁻]+)$/iu',
            $value,
        ) === 1;
    }

    public static function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
