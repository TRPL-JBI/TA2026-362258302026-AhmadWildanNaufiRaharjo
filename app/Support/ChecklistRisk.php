<?php

namespace App\Support;

class ChecklistRisk
{
    public static function levelFromScore(int $score): string
    {
        if ($score <= 3) {
            return 'Rendah';
        }

        if ($score <= 7) {
            return 'Sedang';
        }

        if ($score <= 12) {
            return 'Tinggi';
        }

        return 'Sangat Tinggi';
    }
}
