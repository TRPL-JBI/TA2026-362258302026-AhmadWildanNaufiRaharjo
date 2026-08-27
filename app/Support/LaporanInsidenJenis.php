<?php

namespace App\Support;

final class LaporanInsidenJenis
{
    public const KEBAKARAN = 'Kebakaran';

    public const KECELAKAAN_KERJA = 'Kecelakaan Kerja';

    public const BENCANA_ALAM = 'Bencana Alam';

    public const GANGGUAN_KEAMANAN = 'Gangguan Keamanan';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::KEBAKARAN,
            self::KECELAKAAN_KERJA,
            self::BENCANA_ALAM,
            self::GANGGUAN_KEAMANAN,
        ];
    }
}
