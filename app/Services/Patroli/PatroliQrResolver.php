<?php

namespace App\Services\Patroli;

use App\Models\Apar;
use App\Models\Lokasi;
use Carbon\Carbon;

class PatroliQrResolver
{
    public function __construct(
        private readonly PatroliChecklistResolver $checklistResolver,
    ) {}

    /**
     * @return array{type: string, section?: array<string, mixed>, apar?: array<string, mixed>, message?: string}
     */
    public function resolve(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return ['type' => 'unknown', 'message' => 'Payload QR kosong.'];
        }

        $parsed = $this->parsePayload($raw);

        if (is_array($parsed)) {
            $type = strtolower((string) ($parsed['type'] ?? ''));

            if ($type === 'lokasi') {
                return $this->resolveLokasi($parsed);
            }

            if ($type === 'apar') {
                return $this->resolveApar($parsed);
            }
        }

        if ($lokasi = $this->findLokasiByKode($raw)) {
            return $this->resolveLokasi(['id' => $lokasi->id]);
        }

        if ($apar = $this->findAparByKode($raw)) {
            return $this->resolveApar(['id' => $apar->id, 'kode' => $apar->kode_apar]);
        }

        return ['type' => 'unknown', 'message' => 'QR tidak dikenali. Pastikan memindai QR lokasi atau APAR dari inventaris.'];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{type: string, section?: array<string, mixed>, message?: string}
     */
    private function resolveLokasi(array $parsed): array
    {
        $lokasi = $this->findLokasi($parsed);

        if ($lokasi === null) {
            return ['type' => 'lokasi', 'message' => 'Lokasi tidak ditemukan di inventaris.'];
        }

        $section = $this->checklistResolver->sectionPayload($lokasi);

        if ($section === null) {
            return [
                'type' => 'lokasi',
                'message' => "Belum ada checklist aktif untuk lokasi «{$lokasi->nama_lokasi}». Buat checklist di menu Inventaris terlebih dahulu.",
            ];
        }

        return ['type' => 'lokasi', 'section' => $section];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{type: string, apar?: array<string, mixed>, message?: string}
     */
    private function resolveApar(array $parsed): array
    {
        $apar = $this->findApar($parsed);

        if ($apar === null) {
            return ['type' => 'apar', 'message' => 'APAR tidak ditemukan di inventaris.'];
        }

        $apar->loadMissing('lokasi');

        return [
            'type' => 'apar',
            'apar' => $this->aparPayload($apar),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function aparPayload(Apar $apar): array
    {
        $apar->loadMissing('lokasi');
        $lokasi = $apar->lokasi;
        $expiredStatus = $apar->expiredStatus();
        $daysLeft = null;

        if ($expiredStatus === 'warning') {
            $daysLeft = (int) Carbon::today()->diffInDays($apar->tanggal_expired, false);
        }

        return [
            'type' => 'apar',
            'id' => $apar->id,
            'apar_id' => $apar->id,
            'kodeApar' => $apar->kode_apar,
            'lokasiApar' => $lokasi?->nama_lokasi ?? '',
            'jenisKapasitas' => $apar->jenisKapasitasLabel(),
            'tanggalPemeriksaan' => Carbon::today()->translatedFormat('d F Y'),
            'tanggalExpired' => $apar->tanggal_expired->translatedFormat('d F Y'),
            'tanggalExpiredIso' => $apar->tanggal_expired->format('Y-m-d'),
            'expiredStatus' => $expiredStatus,
            'daysLeft' => $daysLeft,
            'kondisiTabung' => '',
            'kondisiSegel' => null,
            'fotoKondisi' => [],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parsePayload(string $raw): ?array
    {
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function findLokasi(array $parsed): ?Lokasi
    {
        if (isset($parsed['id']) && is_numeric($parsed['id'])) {
            return Lokasi::query()->find((int) $parsed['id']);
        }

        $kode = $parsed['kode'] ?? $parsed['kode_lokasi'] ?? null;

        if (is_string($kode) && $kode !== '') {
            return $this->findLokasiByKode($kode);
        }

        $nama = $parsed['nama'] ?? $parsed['nama_lokasi'] ?? null;

        if (is_string($nama) && $nama !== '') {
            return Lokasi::query()->where('nama_lokasi', $nama)->first();
        }

        return null;
    }

    private function findLokasiByKode(string $kode): ?Lokasi
    {
        return Lokasi::query()->where('kode_lokasi', $kode)->first();
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function findApar(array $parsed): ?Apar
    {
        if (isset($parsed['id']) && is_numeric($parsed['id'])) {
            return Apar::query()->find((int) $parsed['id']);
        }

        $kode = $parsed['kode']
            ?? $parsed['kode_apar']
            ?? $parsed['kodeApar']
            ?? null;

        if (is_string($kode) && $kode !== '') {
            return $this->findAparByKode($kode);
        }

        return null;
    }

    private function findAparByKode(string $kode): ?Apar
    {
        return Apar::query()->where('kode_apar', $kode)->first();
    }
}
