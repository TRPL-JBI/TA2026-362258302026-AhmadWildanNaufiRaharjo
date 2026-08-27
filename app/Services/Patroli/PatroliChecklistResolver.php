<?php

namespace App\Services\Patroli;

use App\Models\ItemChecklist;
use App\Models\Lokasi;
use App\Models\MasterChecklist;

class PatroliChecklistResolver
{
    public function activeChecklistFor(Lokasi $lokasi): ?MasterChecklist
    {
        $checklist = $this->masterChecklistFor($lokasi);

        if ($checklist === null) {
            return null;
        }

        $checklist->load([
            'items' => fn ($query) => $query
                ->where('status', 'Aktif')
                ->orderBy('urutan')
                ->orderBy('id'),
        ]);

        return $checklist->items->isEmpty() ? null : $checklist;
    }

    public function masterChecklistFor(Lokasi $lokasi): ?MasterChecklist
    {
        $pengelola = in_array($lokasi->jenis_lokasi, ['Gedung', 'Ruangan'], true)
            ? 'Petugas K3LH'
            : 'Kalab';

        return MasterChecklist::query()
            ->where('lokasi_id', $lokasi->id)
            ->where('jenis_pengelola', $pengelola)
            ->where('status', 'Aktif')
            ->first();
    }

    public function masterChecklistForAsOf(Lokasi $lokasi, \Carbon\Carbon $asOf): ?MasterChecklist
    {
        $pengelola = in_array($lokasi->jenis_lokasi, ['Gedung', 'Ruangan'], true)
            ? 'Petugas K3LH'
            : 'Kalab';

        return MasterChecklist::query()
            ->where('lokasi_id', $lokasi->id)
            ->where('jenis_pengelola', $pengelola)
            ->where('status', 'Aktif')
            ->where('created_at', '<=', $asOf)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Format section untuk halaman checklist patroli (Alpine.js).
     *
     * @return array<string, mixed>|null
     */
    public function sectionPayload(Lokasi $lokasi): ?array
    {
        $checklist = $this->activeChecklistFor($lokasi);

        if ($checklist === null) {
            return null;
        }

        $items = $checklist->items->map(fn (ItemChecklist $item) => [
            'id' => $item->id,
            'namaItem' => $item->nama_item,
            'probability' => (int) $item->probability,
            'severity' => (int) $item->severity,
            'status' => 'belum',
            'analisaRisiko' => '',
            'rekomendasi' => '',
            'fotoDokumentasi' => [],
        ])->values()->all();

        if ($items === []) {
            return null;
        }

        return [
            'id' => $lokasi->id,
            'nama' => $lokasi->nama_lokasi,
            'namaChecklist' => $checklist->nama_checklist,
            'master_checklist_id' => $checklist->id,
            'expanded' => true,
            'items' => $items,
        ];
    }
}
