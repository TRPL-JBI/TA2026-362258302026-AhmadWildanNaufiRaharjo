<?php

namespace App\Services\TindakLanjut;

use App\Models\DetailInspeksi;
use App\Models\LaporanInsiden;
use App\Support\PatroliPeriode;
use Carbon\Carbon;

class TindakLanjutPeriodeService
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public function periodeOptions(): array
    {
        $keys = collect([PatroliPeriode::keyFromDate(now())]);

        LaporanInsiden::query()
            ->pluck('tanggal_waktu')
            ->each(fn ($date) => $keys->push(PatroliPeriode::keyFromDate(Carbon::parse($date))));

        DetailInspeksi::query()
            ->where('status', DetailInspeksi::STATUS_TIDAK)
            ->with('inspeksi:id,tanggal_inspeksi')
            ->get()
            ->each(function (DetailInspeksi $detail) use ($keys) {
                $date = $detail->inspeksi?->tanggal_inspeksi ?? $detail->created_at;

                if ($date !== null) {
                    $keys->push(PatroliPeriode::keyFromDate(Carbon::parse($date)));
                }

                $selesai = $detail->tindakLanjut?->tanggal_selesai;

                if ($selesai !== null) {
                    $keys->push(PatroliPeriode::keyFromDate(Carbon::parse($selesai)));
                }
            });

        LaporanInsiden::query()
            ->with('tindakLanjut')
            ->get()
            ->each(function (LaporanInsiden $laporan) use ($keys) {
                $selesai = $laporan->tindakLanjut?->tanggal_selesai;

                if ($selesai !== null) {
                    $keys->push(PatroliPeriode::keyFromDate(Carbon::parse($selesai)));
                }
            });

        return $keys
            ->unique()
            ->sortByDesc(fn (string $key) => $this->sortWeight($key))
            ->values()
            ->map(fn (string $key) => [
                'value' => $key,
                'label' => PatroliPeriode::displayLabel($key),
            ])
            ->all();
    }

    public function originKeyFromInspeksi(DetailInspeksi $detail): string
    {
        $date = $detail->inspeksi?->tanggal_inspeksi ?? $detail->created_at;

        return PatroliPeriode::keyFromDate(Carbon::parse($date));
    }

    public function originKeyFromInsiden(LaporanInsiden $laporan): string
    {
        return PatroliPeriode::keyFromDate(Carbon::parse($laporan->tanggal_waktu));
    }

    /**
     * Item tampil di periode P jika:
     * - belum selesai dan periode asal <= P (carry-over), atau
     * - selesai dan P berada di rentang periode asal s/d periode selesai (inklusif).
     *
     * @param  array<string, mixed>  $item
     */
    public function isVisibleInPeriode(array $item, string $viewPeriode): bool
    {
        if (! PatroliPeriode::isValidKey($viewPeriode)) {
            return false;
        }

        $origin = (string) ($item['periode_asal'] ?? '');

        if ($origin === '' || ! PatroliPeriode::isValidKey($origin)) {
            return false;
        }

        if ($this->compareKeys($origin, $viewPeriode) > 0) {
            return false;
        }

        $status = (string) ($item['status'] ?? '');

        if ($status !== 'Selesai') {
            return true;
        }

        $selesai = $item['tanggal_selesai'] ?? null;

        if ($selesai === null || $selesai === '') {
            return false;
        }

        $selesaiKey = PatroliPeriode::keyFromDate(Carbon::parse($selesai));

        return $this->compareKeys($viewPeriode, $selesaiKey) <= 0;
    }

    public function compareKeys(string $a, string $b): int
    {
        $pa = PatroliPeriode::parse($a);
        $pb = PatroliPeriode::parse($b);

        if ($pa['year'] !== $pb['year']) {
            return $pa['year'] <=> $pb['year'];
        }

        return $pa['caturwulan'] <=> $pb['caturwulan'];
    }

    private function sortWeight(string $periodeKey): int
    {
        $parsed = PatroliPeriode::parse($periodeKey);

        return ($parsed['year'] * 10) + $parsed['caturwulan'];
    }
}
