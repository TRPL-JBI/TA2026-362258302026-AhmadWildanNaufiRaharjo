<?php

namespace App\Http\Controllers\Inventaris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventaris\StoreLokasiRequest;
use App\Http\Requests\Inventaris\UpdateLokasiRequest;
use App\Models\Lokasi;
use App\Services\LokasiQrCodeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LokasiController extends Controller
{
    public function __construct(
        private readonly LokasiQrCodeService $qrCodeService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $lokasi = Lokasi::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('nama_lokasi', 'like', "%{$search}%")
                        ->orWhere('kode_lokasi', 'like', "%{$search}%")
                        ->orWhere('jenis_lokasi', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_lokasi')
            ->paginate(10)
            ->withQueryString();

        return view('inventaris.lokasi', [
            'lokasi' => $lokasi,
            'search' => $search,
            'jenisOptions' => ['Gedung', 'Laboratorium', 'Ruangan'],
        ]);
    }

    public function store(StoreLokasiRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $lokasi = Lokasi::query()->create([
            'kode_lokasi' => LokasiQrCodeService::generateKodeLokasi($validated['jenis_lokasi']),
            'nama_lokasi' => $validated['nama_lokasi'],
            'jenis_lokasi' => $validated['jenis_lokasi'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        $lokasi->update([
            'qr_code_path' => $this->qrCodeService->generate($lokasi),
        ]);

        return redirect()
            ->route('inventaris.lokasi')
            ->with('success', 'Lokasi berhasil ditambahkan. QR Code telah dibuat otomatis.');
    }

    public function update(UpdateLokasiRequest $request, Lokasi $lokasi): RedirectResponse
    {
        $validated = $request->validated();
        $jenisChanged = $lokasi->jenis_lokasi !== $validated['jenis_lokasi'];

        $lokasi->fill([
            'nama_lokasi' => $validated['nama_lokasi'],
            'jenis_lokasi' => $validated['jenis_lokasi'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        if ($jenisChanged) {
            $lokasi->kode_lokasi = LokasiQrCodeService::generateKodeLokasi($validated['jenis_lokasi']);
        }

        $lokasi->save();
        $lokasi->update([
            'qr_code_path' => $this->qrCodeService->generate($lokasi),
        ]);

        return redirect()
            ->route('inventaris.lokasi')
            ->with('success', 'Data lokasi berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi): RedirectResponse
    {
        try {
            $this->qrCodeService->deleteFile($lokasi->qr_code_path);
            $lokasi->delete();
        } catch (QueryException) {
            return redirect()
                ->route('inventaris.lokasi')
                ->with('error', 'Lokasi tidak dapat dihapus karena masih digunakan data lain (APAR, checklist, inspeksi, dll).');
        }

        return redirect()
            ->route('inventaris.lokasi')
            ->with('success', 'Lokasi berhasil dihapus.');
    }

    public function printQr(Lokasi $lokasi): View
    {
        $this->ensureQrFile($lokasi);

        return view('inventaris.lokasi-qr-print', [
            'items' => [[
                'lokasi' => $lokasi,
                'qrImageUrl' => route('inventaris.lokasi.qr.image', $lokasi),
            ]],
        ]);
    }

    public function printQrBatch(Request $request): View|RedirectResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('inventaris.lokasi')
                ->with('error', 'Pilih minimal satu lokasi untuk dicetak.');
        }

        if ($ids->count() > 40) {
            return redirect()
                ->route('inventaris.lokasi')
                ->with('error', 'Maksimal 40 QR Code per sekali cetak.');
        }

        $lokasiList = Lokasi::query()
            ->whereIn('id', $ids)
            ->orderBy('nama_lokasi')
            ->get();

        if ($lokasiList->isEmpty()) {
            return redirect()
                ->route('inventaris.lokasi')
                ->with('error', 'Lokasi yang dipilih tidak ditemukan.');
        }

        $items = $lokasiList->map(function (Lokasi $lokasi) {
            $this->ensureQrFile($lokasi);

            return [
                'lokasi' => $lokasi,
                'qrImageUrl' => route('inventaris.lokasi.qr.image', $lokasi),
            ];
        })->all();

        return view('inventaris.lokasi-qr-print', [
            'items' => $items,
        ]);
    }

    public function qrImage(Lokasi $lokasi): BinaryFileResponse
    {
        $this->ensureQrFile($lokasi);

        return response()->file(
            Storage::disk('public')->path($lokasi->qr_code_path),
            [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="qr-'.$lokasi->kode_lokasi.'.svg"',
            ],
        );
    }

    private function ensureQrFile(Lokasi $lokasi): void
    {
        $path = $this->qrCodeService->generate($lokasi);

        if ($lokasi->qr_code_path !== $path) {
            $lokasi->update(['qr_code_path' => $path]);
            $lokasi->refresh();
        }
    }
}
