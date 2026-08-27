<?php

namespace App\Http\Requests\Pemantauan;

use App\Models\LaporanLimbahB3;

class UpdateLaporanB3Request extends StoreLaporanB3Request
{
    protected function resolveLaporanIdForUniqueCheck(): ?int
    {
        $laporan = $this->route('laporanLimbahB3');

        return $laporan instanceof LaporanLimbahB3 ? $laporan->id : null;
    }
}
