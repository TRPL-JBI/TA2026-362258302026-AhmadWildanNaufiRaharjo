<?php

namespace App\Http\Controllers\Inventaris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventaris\StoreTitikIpamRequest;
use App\Http\Requests\Inventaris\StoreUnitIpamRequest;
use App\Http\Requests\Inventaris\UpdateTitikIpamRequest;
use App\Http\Requests\Inventaris\UpdateUnitIpamRequest;
use App\Models\TitikIpam;
use App\Models\UnitIpam;
use App\Services\UnitIpamCodeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IpamController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $filterUnit = $request->query('unit', 'semua');

        $titik = TitikIpam::query()
            ->with('unitIpam')
            ->when($filterUnit !== '' && $filterUnit !== 'semua', function ($query) use ($filterUnit) {
                $query->where('unit_ipam_id', (int) $filterUnit);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('titik_lokasi', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhereHas('unitIpam', function ($unitQuery) use ($search) {
                            $unitQuery->where('nama_unit', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('unit_ipam_id')
            ->orderBy('titik_lokasi')
            ->paginate(10)
            ->withQueryString();

        $units = UnitIpam::query()
            ->withCount('titikIpam')
            ->orderBy('nama_unit')
            ->get();

        return view('inventaris.ipam', [
            'titik' => $titik,
            'units' => $units,
            'search' => $search,
            'filterUnit' => $filterUnit,
        ]);
    }

    public function storeUnit(StoreUnitIpamRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        UnitIpam::query()->create([
            'kode_unit' => UnitIpamCodeService::generateKodeUnit(),
            'nama_unit' => $validated['nama_unit'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()
            ->route('inventaris.ipam')
            ->with('success', 'Unit IPAM berhasil ditambahkan.');
    }

    public function updateUnit(UpdateUnitIpamRequest $request, UnitIpam $unitIpam): RedirectResponse
    {
        $validated = $request->validated();

        $unitIpam->update([
            'nama_unit' => $validated['nama_unit'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()
            ->route('inventaris.ipam')
            ->with('success', 'Unit IPAM berhasil diperbarui.');
    }

    public function destroyUnit(UnitIpam $unitIpam): RedirectResponse
    {
        if ($unitIpam->titikIpam()->exists()) {
            return redirect()
                ->route('inventaris.ipam')
                ->with('error', 'Unit tidak dapat dihapus karena masih memiliki titik sampling.');
        }

        try {
            $unitIpam->delete();
        } catch (QueryException) {
            return redirect()
                ->route('inventaris.ipam')
                ->with('error', 'Unit IPAM tidak dapat dihapus karena masih digunakan data lain.');
        }

        return redirect()
            ->route('inventaris.ipam')
            ->with('success', 'Unit IPAM berhasil dihapus.');
    }

    public function storeTitik(StoreTitikIpamRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        TitikIpam::query()->create([
            'unit_ipam_id' => $validated['unit_ipam_id'],
            'titik_lokasi' => $validated['titik_lokasi'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()
            ->route('inventaris.ipam')
            ->with('success', 'Titik IPAM berhasil ditambahkan.');
    }

    public function updateTitik(UpdateTitikIpamRequest $request, TitikIpam $titikIpam): RedirectResponse
    {
        $validated = $request->validated();

        $titikIpam->update([
            'unit_ipam_id' => $validated['unit_ipam_id'],
            'titik_lokasi' => $validated['titik_lokasi'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return redirect()
            ->route('inventaris.ipam')
            ->with('success', 'Titik IPAM berhasil diperbarui.');
    }

    public function destroyTitik(TitikIpam $titikIpam): RedirectResponse
    {
        try {
            $titikIpam->delete();
        } catch (QueryException) {
            return redirect()
                ->route('inventaris.ipam')
                ->with('error', 'Titik IPAM tidak dapat dihapus karena masih digunakan laporan pemantauan.');
        }

        return redirect()
            ->route('inventaris.ipam')
            ->with('success', 'Titik IPAM berhasil dihapus.');
    }
}
