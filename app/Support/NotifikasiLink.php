<?php

namespace App\Support;

use App\Models\Apar;
use App\Models\Notifikasi;

class NotifikasiLink
{
    public static function forApar(Apar $apar, string $type): string
    {
        return route('inventaris.apar', array_filter([
            'q' => $apar->kode_apar,
            'status_expired' => $type === 'expired' ? 'expired' : 'warning',
        ]));
    }

    public static function for(Notifikasi $notifikasi, ?string $aparKode = null): ?string
    {
        return match ($notifikasi->jenis_notifikasi) {
            'Early Warning APAR' => route('inventaris.apar', array_filter([
                'q' => $aparKode,
                'status_expired' => str_starts_with($notifikasi->judul, 'APAR sudah expired')
                    ? 'expired'
                    : (str_starts_with($notifikasi->judul, 'APAR akan expired') ? 'warning' : null),
            ])),
            'Laporan Insiden' => route('tindak-lanjut'),
            default => null,
        };
    }

    /**
     * @param  iterable<int, Notifikasi>  $notifikasi
     * @return array<int, string>
     */
    public static function aparKodesByReference(iterable $notifikasi): array
    {
        $aparIds = collect($notifikasi)
            ->filter(fn (Notifikasi $item) => $item->jenis_notifikasi === 'Early Warning APAR' && $item->reference_id)
            ->pluck('reference_id')
            ->unique()
            ->values();

        if ($aparIds->isEmpty()) {
            return [];
        }

        return Apar::query()
            ->whereIn('id', $aparIds)
            ->pluck('kode_apar', 'id')
            ->all();
    }
}
