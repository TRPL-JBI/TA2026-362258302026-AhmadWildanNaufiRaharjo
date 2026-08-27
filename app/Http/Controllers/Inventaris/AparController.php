<?php

namespace App\Http\Controllers\Inventaris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventaris\StoreAparRequest;
use App\Http\Requests\Inventaris\UpdateAparRequest;
use App\Models\Apar;
use App\Models\Lokasi;
use App\Services\AparQrCodeService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AparController extends Controller
{
    public function __construct(
        private readonly AparQrCodeService $qrCodeService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $jenis = (string) $request->query('jenis', '');
        $kondisi = (string) $request->query('kondisi', '');
        $statusExpired = (string) $request->query('status_expired', '');
        $today = Carbon::today();

        $apar = Apar::query()
            ->with('lokasi')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('kode_apar', 'like', "%{$search}%")
                        ->orWhere('jenis_apar', 'like', "%{$search}%")
                        ->orWhereHas('lokasi', function ($lokasiQuery) use ($search) {
                            $lokasiQuery->where('nama_lokasi', 'like', "%{$search}%");
                        });
                });
            })
            ->when($jenis !== '', fn ($query) => $query->where('jenis_apar', $jenis))
            ->when(in_array($kondisi, Apar::kondisiOptions(), true), fn ($query) => $query->where('status_kondisi', $kondisi))
            ->when($statusExpired === 'expired', fn ($query) => $query->whereDate('tanggal_expired', '<', $today))
            ->when($statusExpired === 'warning', function ($query) use ($today) {
                $query
                    ->whereDate('tanggal_expired', '>=', $today)
                    ->whereDate('tanggal_expired', '<=', $today->copy()->addDays(30));
            })
            ->when($statusExpired === 'normal', fn ($query) => $query->whereDate('tanggal_expired', '>', $today->copy()->addDays(30)))
            ->orderBy('kode_apar')
            ->paginate(10)
            ->withQueryString();

        $nearExpiredCount = Apar::query()
            ->where(function ($query) use ($today) {
                $query
                    ->whereDate('tanggal_expired', '<', $today)
                    ->orWhere(function ($warningQuery) use ($today) {
                        $warningQuery
                            ->whereDate('tanggal_expired', '>=', $today)
                            ->whereDate('tanggal_expired', '<=', $today->copy()->addDays(30));
                    });
            })
            ->count();

        return view('inventaris.apar', [
            'apar' => $apar,
            'lokasiOptions' => Lokasi::query()->orderBy('nama_lokasi')->get(['id', 'nama_lokasi']),
            'search' => $search,
            'jenis' => $jenis,
            'kondisi' => $kondisi,
            'statusExpired' => $statusExpired,
            'nearExpiredCount' => $nearExpiredCount,
            'jenisOptions' => ['Powder', 'CO2', 'Foam'],
            'kondisiOptions' => Apar::kondisiOptions(),
        ]);
    }

    public function store(StoreAparRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $lokasi = Lokasi::query()->findOrFail($validated['lokasi_id']);

        $apar = Apar::query()->create([
            'lokasi_id' => $lokasi->id,
            'kode_apar' => AparQrCodeService::generateKodeApar($lokasi),
            'jenis_apar' => $validated['jenis_apar'],
            'kapasitas_kg' => $validated['kapasitas_kg'],
            'tanggal_expired' => $validated['tanggal_expired'],
            'keterangan' => $validated['keterangan'] ?? null,
            'is_notified' => false,
        ]);

        $apar->update([
            'qr_code_path' => $this->qrCodeService->generate($apar),
        ]);

        return redirect()
            ->route('inventaris.apar')
            ->with('success', 'APAR berhasil ditambahkan. QR Code telah dibuat otomatis.');
    }

    public function update(UpdateAparRequest $request, Apar $apar): RedirectResponse
    {
        $validated = $request->validated();

        $apar->fill([
            'lokasi_id' => $validated['lokasi_id'],
            'jenis_apar' => $validated['jenis_apar'],
            'kapasitas_kg' => $validated['kapasitas_kg'],
            'tanggal_expired' => $validated['tanggal_expired'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        if ($apar->isDirty('tanggal_expired')) {
            $apar->is_notified = false;
        }

        $apar->save();
        $apar->update([
            'qr_code_path' => $this->qrCodeService->generate($apar),
        ]);

        return redirect()
            ->route('inventaris.apar')
            ->with('success', 'Data APAR berhasil diperbarui.');
    }

    public function destroy(Apar $apar): RedirectResponse
    {
        try {
            $this->qrCodeService->deleteFile($apar->qr_code_path);
            $apar->delete();
        } catch (QueryException) {
            return redirect()
                ->route('inventaris.apar')
                ->with('error', 'APAR tidak dapat dihapus karena masih memiliki riwayat pemeriksaan.');
        }

        return redirect()
            ->route('inventaris.apar')
            ->with('success', 'APAR berhasil dihapus.');
    }

    public function printQr(Apar $apar): View
    {
        $this->ensureQrFile($apar);
        $apar->load('lokasi');

        return view('inventaris.apar-qr-print', [
            'apar' => $apar,
            'qrImageUrl' => route('inventaris.apar.qr.image', $apar),
        ]);
    }

    public function qrImage(Apar $apar): BinaryFileResponse
    {
        $this->ensureQrFile($apar);

        return response()->file(
            Storage::disk('public')->path($apar->qr_code_path),
            [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="qr-'.$apar->kode_apar.'.svg"',
            ],
        );
    }

    private function ensureQrFile(Apar $apar): void
    {
        $path = $this->qrCodeService->generate($apar);

        if ($apar->qr_code_path !== $path) {
            $apar->update(['qr_code_path' => $path]);
            $apar->refresh();
        }
    }
}
