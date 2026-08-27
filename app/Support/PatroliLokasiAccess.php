<?php

namespace App\Support;

use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PatroliLokasiAccess
{
    /**
     * Semua lokasi inventaris untuk tampilan patroli (tanpa filter role/jenis).
     *
     * @return Collection<int, Lokasi>
     */
    public static function allLokasi(): Collection
    {
        return Lokasi::query()
            ->orderBy('nama_lokasi')
            ->get(['id', 'nama_lokasi', 'jenis_lokasi']);
    }

    /**
     * Apakah user boleh membuat checklist dari halaman patroli untuk lokasi ini.
     * Patroli: Petugas K3LH boleh untuk semua jenis lokasi (termasuk laboratorium).
     */
    public static function canCreateChecklist(User $user, Lokasi $lokasi): bool
    {
        if ($user->hasRole('Petugas K3LH')) {
            return true;
        }

        if ($user->hasRole('Kalab')) {
            return $user->lokasi_id !== null
                && (int) $lokasi->id === (int) $user->lokasi_id
                && $lokasi->jenis_lokasi === 'Laboratorium';
        }

        return false;
    }

    public static function pengelolaFor(Lokasi $lokasi): string
    {
        return in_array($lokasi->jenis_lokasi, ['Gedung', 'Ruangan'], true)
            ? 'Petugas K3LH'
            : 'Kalab';
    }

    public static function resolveForChecklistCreation(User $user, int $lokasiId): Lokasi
    {
        $lokasi = Lokasi::query()->findOrFail($lokasiId);

        if (! self::canCreateChecklist($user, $lokasi)) {
            abort(422, 'Anda tidak dapat membuat checklist untuk lokasi ini.');
        }

        return $lokasi;
    }

    /**
     * Semua checklist aktif untuk dropdown tambah item di halaman patroli.
     *
     * @return Builder<MasterChecklist>
     */
    public static function checklistQueryForPatroli(User $user): Builder
    {
        if (! $user->hasRole('Petugas K3LH', 'Kalab')) {
            throw new AccessDeniedHttpException;
        }

        $query = MasterChecklist::query()
            ->with('lokasi:id,nama_lokasi,jenis_lokasi')
            ->where('status', 'Aktif');

        if ($user->hasRole('Kalab')) {
            return $query
                ->where('jenis_pengelola', 'Kalab')
                ->where('lokasi_id', $user->lokasi_id);
        }

        return $query;
    }

    public static function assertCanManageChecklistInPatroli(User $user, MasterChecklist $masterChecklist): void
    {
        if (! $user->hasRole('Petugas K3LH', 'Kalab')) {
            throw new AccessDeniedHttpException;
        }

        $masterChecklist->loadMissing('lokasi');

        if ($masterChecklist->status !== 'Aktif') {
            abort(422, 'Checklist tidak aktif.');
        }

        if ($user->hasRole('Petugas K3LH')) {
            return;
        }

        ChecklistTemuanAccess::assertCanManageChecklist($user, $masterChecklist);
    }
}
