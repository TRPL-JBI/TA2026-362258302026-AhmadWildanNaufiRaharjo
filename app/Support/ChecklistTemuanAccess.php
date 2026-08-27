<?php

namespace App\Support;

use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ChecklistTemuanAccess
{
    public static function canManage(User $user): bool
    {
        return $user->hasRole('Petugas K3LH', 'Kalab');
    }

    /**
     * @return Builder<MasterChecklist>
     */
    public static function checklistQueryFor(User $user): Builder
    {
        $query = MasterChecklist::query()
            ->with([
                'lokasi:id,nama_lokasi,jenis_lokasi',
                'items' => fn ($builder) => $builder->orderBy('urutan')->orderBy('id'),
            ]);

        if ($user->hasRole('Petugas K3LH')) {
            return $query
                ->where('jenis_pengelola', 'Petugas K3LH')
                ->whereHas(
                    'lokasi',
                    fn ($builder) => $builder->whereIn('jenis_lokasi', ['Gedung', 'Ruangan']),
                );
        }

        if ($user->hasRole('Kalab')) {
            return $query
                ->where('jenis_pengelola', 'Kalab')
                ->where('lokasi_id', $user->lokasi_id);
        }

        throw new AccessDeniedHttpException;
    }

    /**
     * @return Collection<int, Lokasi>
     */
    public static function lokasiOptionsFor(User $user): Collection
    {
        if ($user->hasRole('Petugas K3LH')) {
            return Lokasi::query()
                ->whereIn('jenis_lokasi', ['Gedung', 'Ruangan'])
                ->orderBy('nama_lokasi')
                ->get(['id', 'nama_lokasi', 'jenis_lokasi']);
        }

        if ($user->hasRole('Kalab') && $user->lokasi_id !== null) {
            return Lokasi::query()
                ->where('id', $user->lokasi_id)
                ->where('jenis_lokasi', 'Laboratorium')
                ->get(['id', 'nama_lokasi', 'jenis_lokasi']);
        }

        return collect();
    }

    public static function managerTypeFor(User $user): string
    {
        return match (true) {
            $user->hasRole('Petugas K3LH') => 'Petugas K3LH',
            $user->hasRole('Kalab') => 'Kalab',
            default => throw new AccessDeniedHttpException,
        };
    }

    public static function assertCanManageChecklist(User $user, MasterChecklist $masterChecklist): void
    {
        $masterChecklist->loadMissing('lokasi');

        if ($user->hasRole('Petugas K3LH')) {
            if ($masterChecklist->jenis_pengelola !== 'Petugas K3LH') {
                throw new AccessDeniedHttpException;
            }

            if (! in_array($masterChecklist->lokasi?->jenis_lokasi, ['Gedung', 'Ruangan'], true)) {
                throw new AccessDeniedHttpException;
            }

            return;
        }

        if ($user->hasRole('Kalab')) {
            if (
                $masterChecklist->jenis_pengelola !== 'Kalab'
                || $masterChecklist->lokasi_id !== $user->lokasi_id
            ) {
                throw new AccessDeniedHttpException;
            }

            return;
        }

        throw new AccessDeniedHttpException;
    }

    public static function resolveLokasiFor(User $user, int $lokasiId): Lokasi
    {
        $lokasi = Lokasi::query()->findOrFail($lokasiId);

        if ($user->hasRole('Petugas K3LH')) {
            if (! in_array($lokasi->jenis_lokasi, ['Gedung', 'Ruangan'], true)) {
                abort(422, 'Petugas K3LH hanya dapat mengelola checklist di gedung dan ruangan, bukan laboratorium.');
            }

            return $lokasi;
        }

        if ($user->hasRole('Kalab')) {
            if ($user->lokasi_id === null || $lokasi->id !== $user->lokasi_id) {
                abort(422, 'Kalab hanya dapat mengelola checklist untuk laboratorium yang menjadi tanggung jawabnya.');
            }

            if ($lokasi->jenis_lokasi !== 'Laboratorium') {
                abort(422, 'Checklist Kalab harus dikaitkan ke lokasi laboratorium.');
            }

            return $lokasi;
        }

        throw new AccessDeniedHttpException;
    }
}
