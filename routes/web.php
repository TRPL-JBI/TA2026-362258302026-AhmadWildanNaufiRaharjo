<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Sop\SopDokumenController;
use App\Http\Controllers\Inventaris\AparController;
use App\Http\Controllers\Inventaris\ChecklistTemuanController;
use App\Http\Controllers\Inventaris\IpamController;
use App\Http\Controllers\Inventaris\LokasiController;
use App\Http\Controllers\Inventaris\UserController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanInsiden\LaporanInsidenController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Patroli\PatroliAparController;
use App\Http\Controllers\Patroli\PatroliRiwayatController;
use App\Http\Controllers\Patroli\PatroliScanController;
use App\Http\Controllers\Patroli\PatroliTemuanController;
use App\Http\Controllers\Pemantauan\PemantauanB3Controller;
use App\Http\Controllers\Pemantauan\PemantauanIpalController;
use App\Http\Controllers\Pemantauan\PemantauanIpamController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TindakLanjutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'role.access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/read-all', [NotifikasiController::class, 'markAllRead'])->name('notifikasi.read-all');
    Route::post('/notifikasi/{notifikasi}/read', [NotifikasiController::class, 'markRead'])->name('notifikasi.read');

    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    Route::redirect('/manajemen-user', '/inventaris/user');

    Route::get('/inventaris/user', [UserController::class, 'index'])->name('inventaris.user');
    Route::post('/inventaris/user', [UserController::class, 'store'])->name('inventaris.user.store');
    Route::put('/inventaris/user/{user}', [UserController::class, 'update'])->name('inventaris.user.update');
    Route::delete('/inventaris/user/{user}', [UserController::class, 'destroy'])->name('inventaris.user.destroy');

    Route::get('/inventaris/lokasi', [LokasiController::class, 'index'])->name('inventaris.lokasi');
    Route::post('/inventaris/lokasi', [LokasiController::class, 'store'])->name('inventaris.lokasi.store');
    Route::get('/inventaris/lokasi/qr/print-batch', [LokasiController::class, 'printQrBatch'])->name('inventaris.lokasi.qr.print-batch');
    Route::put('/inventaris/lokasi/{lokasi}', [LokasiController::class, 'update'])->name('inventaris.lokasi.update');
    Route::delete('/inventaris/lokasi/{lokasi}', [LokasiController::class, 'destroy'])->name('inventaris.lokasi.destroy');
    Route::get('/inventaris/lokasi/{lokasi}/qr/image', [LokasiController::class, 'qrImage'])->name('inventaris.lokasi.qr.image');
    Route::get('/inventaris/lokasi/{lokasi}/qr/print', [LokasiController::class, 'printQr'])->name('inventaris.lokasi.qr.print');

    Route::get('/inventaris/apar', [AparController::class, 'index'])->name('inventaris.apar');
    Route::post('/inventaris/apar', [AparController::class, 'store'])->name('inventaris.apar.store');
    Route::put('/inventaris/apar/{apar}', [AparController::class, 'update'])->name('inventaris.apar.update');
    Route::delete('/inventaris/apar/{apar}', [AparController::class, 'destroy'])->name('inventaris.apar.destroy');
    Route::get('/inventaris/apar/{apar}/qr/image', [AparController::class, 'qrImage'])->name('inventaris.apar.qr.image');
    Route::get('/inventaris/apar/{apar}/qr/print', [AparController::class, 'printQr'])->name('inventaris.apar.qr.print');

    Route::get('/inventaris/ipam', [IpamController::class, 'index'])->name('inventaris.ipam');
    Route::post('/inventaris/ipam/unit', [IpamController::class, 'storeUnit'])->name('inventaris.ipam.unit.store');
    Route::put('/inventaris/ipam/unit/{unitIpam}', [IpamController::class, 'updateUnit'])->name('inventaris.ipam.unit.update');
    Route::delete('/inventaris/ipam/unit/{unitIpam}', [IpamController::class, 'destroyUnit'])->name('inventaris.ipam.unit.destroy');
    Route::post('/inventaris/ipam/titik', [IpamController::class, 'storeTitik'])->name('inventaris.ipam.titik.store');
    Route::put('/inventaris/ipam/titik/{titikIpam}', [IpamController::class, 'updateTitik'])->name('inventaris.ipam.titik.update');
    Route::delete('/inventaris/ipam/titik/{titikIpam}', [IpamController::class, 'destroyTitik'])->name('inventaris.ipam.titik.destroy');

    Route::get('/inventaris/checklist-temuan', [ChecklistTemuanController::class, 'index'])->name('inventaris.checklist-temuan');
    Route::post('/inventaris/checklist-temuan', [ChecklistTemuanController::class, 'store'])->name('inventaris.checklist-temuan.store');
    Route::put('/inventaris/checklist-temuan/{masterChecklist}', [ChecklistTemuanController::class, 'update'])->name('inventaris.checklist-temuan.update');
    Route::delete('/inventaris/checklist-temuan/{masterChecklist}', [ChecklistTemuanController::class, 'destroy'])->name('inventaris.checklist-temuan.destroy');
    Route::patch('/inventaris/checklist-temuan/{masterChecklist}/status', [ChecklistTemuanController::class, 'toggleStatus'])->name('inventaris.checklist-temuan.toggle-status');
    Route::post('/inventaris/checklist-temuan/{masterChecklist}/items', [ChecklistTemuanController::class, 'storeItem'])->name('inventaris.checklist-temuan.items.store');
    Route::put('/inventaris/checklist-temuan/items/{itemChecklist}', [ChecklistTemuanController::class, 'updateItem'])->name('inventaris.checklist-temuan.items.update');
    Route::delete('/inventaris/checklist-temuan/items/{itemChecklist}', [ChecklistTemuanController::class, 'destroyItem'])->name('inventaris.checklist-temuan.items.destroy');
    Route::patch('/inventaris/checklist-temuan/items/{itemChecklist}/status', [ChecklistTemuanController::class, 'toggleItemStatus'])->name('inventaris.checklist-temuan.items.toggle-status');

    Route::get('/sop', [SopDokumenController::class, 'index'])->name('sop');
    Route::get('/sop/{sopDokumen}/preview', [SopDokumenController::class, 'preview'])->name('sop.preview');
    Route::post('/sop', [SopDokumenController::class, 'store'])->name('sop.store');
    Route::put('/sop/{sopDokumen}', [SopDokumenController::class, 'update'])->name('sop.update');
    Route::delete('/sop/{sopDokumen}', [SopDokumenController::class, 'destroy'])->name('sop.destroy');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
    Route::get('/laporan/{laporanGenerated}/preview', [LaporanController::class, 'preview'])->name('laporan.preview');
    Route::get('/laporan/{laporanGenerated}/download', [LaporanController::class, 'download'])->name('laporan.download');

    Route::get('/laporan-insiden', [LaporanInsidenController::class, 'index'])->name('laporan-insiden');
    Route::post('/laporan-insiden', [LaporanInsidenController::class, 'store'])->name('laporan-insiden.store');

    Route::post('/tindak-lanjut/inspeksi/{detailInspeksi}', [TindakLanjutController::class, 'updateInspeksi'])
        ->name('tindak-lanjut.inspeksi.update');
    Route::post('/tindak-lanjut/insiden/{laporanInsiden}', [TindakLanjutController::class, 'updateInsiden'])
        ->name('tindak-lanjut.insiden.update');
    Route::patch('/tindak-lanjut/{periode}/selesai', [TindakLanjutController::class, 'markPeriodeSelesai'])
        ->where('periode', '\d{4}-[1-3]')
        ->name('tindak-lanjut.periode.selesai');
    Route::get('/tindak-lanjut/{periode?}', [TindakLanjutController::class, 'index'])
        ->where('periode', '\d{4}-[1-3]')
        ->name('tindak-lanjut');

    Route::get('/pemantauan/ipam', [PemantauanIpamController::class, 'index'])->name('pemantauan.ipam');
    Route::get('/pemantauan/ipam/{periodeKey}', [PemantauanIpamController::class, 'show'])
        ->where('periodeKey', '\d{4}-\d{1,2}')
        ->name('pemantauan.ipam.show');
    Route::post('/pemantauan/ipam', [PemantauanIpamController::class, 'store'])->name('pemantauan.ipam.store');
    Route::put('/pemantauan/ipam/{periodeKey}', [PemantauanIpamController::class, 'update'])
        ->where('periodeKey', '\d{4}-\d{1,2}')
        ->name('pemantauan.ipam.update');
    Route::patch('/pemantauan/ipam/{periodeKey}/selesai', [PemantauanIpamController::class, 'markSelesai'])
        ->where('periodeKey', '\d{4}-\d{1,2}')
        ->name('pemantauan.ipam.selesai');
    Route::delete('/pemantauan/ipam/{periodeKey}', [PemantauanIpamController::class, 'destroy'])
        ->where('periodeKey', '\d{4}-\d{1,2}')
        ->name('pemantauan.ipam.destroy');

    Route::get('/pemantauan/ipal', [PemantauanIpalController::class, 'index'])->name('pemantauan.ipal');
    Route::get('/pemantauan/ipal/{laporanIpal}', [PemantauanIpalController::class, 'show'])->name('pemantauan.ipal.show');
    Route::post('/pemantauan/ipal', [PemantauanIpalController::class, 'store'])->name('pemantauan.ipal.store');
    Route::put('/pemantauan/ipal/{laporanIpal}', [PemantauanIpalController::class, 'update'])->name('pemantauan.ipal.update');
    Route::patch('/pemantauan/ipal/{laporanIpal}/selesai', [PemantauanIpalController::class, 'markSelesai'])->name('pemantauan.ipal.selesai');
    Route::delete('/pemantauan/ipal/{laporanIpal}', [PemantauanIpalController::class, 'destroy'])->name('pemantauan.ipal.destroy');

    Route::get('/pemantauan/b3', [PemantauanB3Controller::class, 'index'])->name('pemantauan.b3');
    Route::get('/pemantauan/b3/{laporanLimbahB3}', [PemantauanB3Controller::class, 'show'])->name('pemantauan.b3.show');
    Route::post('/pemantauan/b3', [PemantauanB3Controller::class, 'store'])->name('pemantauan.b3.store');
    Route::put('/pemantauan/b3/{laporanLimbahB3}', [PemantauanB3Controller::class, 'update'])->name('pemantauan.b3.update');
    Route::patch('/pemantauan/b3/{laporanLimbahB3}/selesai', [PemantauanB3Controller::class, 'markSelesai'])->name('pemantauan.b3.selesai');
    Route::delete('/pemantauan/b3/{laporanLimbahB3}', [PemantauanB3Controller::class, 'destroy'])->name('pemantauan.b3.destroy');

    Route::get('/patroli/riwayat/{periode?}', [PatroliRiwayatController::class, 'index'])
        ->name('patroli.riwayat')
        ->where('periode', '\d{4}-[1-3]');
    Route::redirect('/patroli/riwayat/temuan', '/patroli/riwayat');
    Route::redirect('/patroli/riwayat/temuan/{periode}', '/patroli/riwayat/{periode}')->where('periode', '\d{4}-[1-3]');
    Route::redirect('/patroli/riwayat/apar', '/patroli/riwayat');
    Route::get('/patroli/riwayat/temuan/{periode}/lanjutkan', [PatroliRiwayatController::class, 'continueTemuan'])->name('patroli.riwayat.temuan.lanjutkan')->where('periode', '\d{4}-[1-3]');
    Route::post('/patroli/riwayat/temuan/{periode}/checklist', [PatroliRiwayatController::class, 'storeChecklist'])
        ->name('patroli.riwayat.temuan.checklist.store')
        ->where('periode', '\d{4}-[1-3]');
    Route::post('/patroli/riwayat/temuan/{periode}/checklist/{masterChecklist}/items', [PatroliRiwayatController::class, 'storeItem'])
        ->name('patroli.riwayat.temuan.items.store')
        ->where('periode', '\d{4}-[1-3]');
    Route::patch('/patroli/riwayat/temuan/{periode}/items/{itemChecklist}/status', [PatroliRiwayatController::class, 'toggleItemStatus'])
        ->name('patroli.riwayat.temuan.items.toggle-status')
        ->where('periode', '\d{4}-[1-3]');
    Route::get('/patroli/riwayat/apar/{periode}', [PatroliRiwayatController::class, 'showApar'])->name('patroli.riwayat.apar')->where('periode', '\d{4}-[1-3]');
    Route::get('/patroli/riwayat/apar/{periode}/lanjutkan', [PatroliRiwayatController::class, 'continueApar'])->name('patroli.riwayat.apar.lanjutkan')->where('periode', '\d{4}-[1-3]');
    Route::patch('/patroli/riwayat/temuan/{periode}/selesai', [PatroliRiwayatController::class, 'markTemuanSelesai'])->name('patroli.riwayat.temuan.selesai')->where('periode', '\d{4}-[1-3]');
    Route::patch('/patroli/riwayat/apar/{periode}/selesai', [PatroliRiwayatController::class, 'markAparSelesai'])->name('patroli.riwayat.apar.selesai')->where('periode', '\d{4}-[1-3]');
    Route::delete('/patroli/riwayat/temuan/{periode}', [PatroliRiwayatController::class, 'destroyTemuan'])->name('patroli.riwayat.temuan.destroy')->where('periode', '\d{4}-[1-3]');
    Route::delete('/patroli/riwayat/apar/{periode}', [PatroliRiwayatController::class, 'destroyApar'])->name('patroli.riwayat.apar.destroy')->where('periode', '\d{4}-[1-3]');

    Route::get('/patroli/scan', [PatroliScanController::class, 'index'])->name('patroli.scan');
    Route::post('/patroli/qr/resolve', [PatroliScanController::class, 'resolve'])->name('patroli.qr.resolve');

    Route::get('/patroli/temuan', [PatroliTemuanController::class, 'index'])->name('patroli.temuan');
    Route::post('/patroli/inspeksi', [PatroliTemuanController::class, 'store'])->name('patroli.inspeksi.store');

    Route::get('/patroli/apar', [PatroliAparController::class, 'index'])->name('patroli.apar');
    Route::post('/patroli/apar', [PatroliAparController::class, 'store'])->name('patroli.apar.store');
});
