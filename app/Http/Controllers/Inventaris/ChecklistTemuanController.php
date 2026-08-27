<?php

namespace App\Http\Controllers\Inventaris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventaris\StoreItemChecklistRequest;
use App\Http\Requests\Inventaris\StoreMasterChecklistRequest;
use App\Http\Requests\Inventaris\UpdateItemChecklistRequest;
use App\Http\Requests\Inventaris\UpdateMasterChecklistRequest;
use App\Models\ItemChecklist;
use App\Models\MasterChecklist;
use App\Support\ChecklistTemuanAccess;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChecklistTemuanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && ChecklistTemuanAccess::canManage($user), 403);

        $checklists = ChecklistTemuanAccess::checklistQueryFor($user)
            ->orderBy('nama_checklist')
            ->get();

        $lokasiOptions = ChecklistTemuanAccess::lokasiOptionsFor($user);

        $roleScope = $user->hasRole('Petugas K3LH')
            ? 'petugas'
            : 'kalab';

        return view('inventaris.checklist-temuan', [
            'checklists' => $checklists->map(fn (MasterChecklist $row) => $this->serializeChecklist($row))->values()->all(),
            'lokasiOptions' => $lokasiOptions->map(fn ($row) => [
                'id' => $row->id,
                'label' => $row->nama_lokasi,
            ])->values()->all(),
            'roleScope' => $roleScope,
            'canCreate' => $lokasiOptions->isNotEmpty(),
        ]);
    }

    public function store(StoreMasterChecklistRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        ChecklistTemuanAccess::resolveLokasiFor($user, (int) $validated['lokasi_id']);

        MasterChecklist::query()->create([
            'nama_checklist' => $validated['nama_checklist'],
            'lokasi_id' => $validated['lokasi_id'],
            'dibuat_oleh_id' => $user->id,
            'jenis_pengelola' => ChecklistTemuanAccess::managerTypeFor($user),
            'status' => 'Aktif',
        ]);

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Checklist berhasil dibuat.');
    }

    public function update(UpdateMasterChecklistRequest $request, MasterChecklist $masterChecklist): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        ChecklistTemuanAccess::assertCanManageChecklist($user, $masterChecklist);
        ChecklistTemuanAccess::resolveLokasiFor($user, (int) $validated['lokasi_id']);

        $masterChecklist->update([
            'nama_checklist' => $validated['nama_checklist'],
            'lokasi_id' => $validated['lokasi_id'],
        ]);

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Checklist berhasil diperbarui.');
    }

    public function destroy(Request $request, MasterChecklist $masterChecklist): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && ChecklistTemuanAccess::canManage($user), 403);

        ChecklistTemuanAccess::assertCanManageChecklist($user, $masterChecklist);

        try {
            $masterChecklist->delete();
        } catch (QueryException) {
            return redirect()
                ->route('inventaris.checklist-temuan')
                ->with('error', 'Checklist tidak dapat dihapus karena masih digunakan pada data inspeksi.');
        }

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Checklist berhasil dihapus.');
    }

    public function toggleStatus(Request $request, MasterChecklist $masterChecklist): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && ChecklistTemuanAccess::canManage($user), 403);

        ChecklistTemuanAccess::assertCanManageChecklist($user, $masterChecklist);

        $masterChecklist->update([
            'status' => $masterChecklist->status === 'Aktif' ? 'Nonaktif' : 'Aktif',
        ]);

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Status checklist diperbarui.');
    }

    public function storeItem(StoreItemChecklistRequest $request, MasterChecklist $masterChecklist): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        ChecklistTemuanAccess::assertCanManageChecklist($user, $masterChecklist);

        $nextUrutan = (int) $masterChecklist->items()->max('urutan') + 1;

        $masterChecklist->items()->create([
            'nama_item' => $validated['nama_item'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'probability' => $validated['probability'],
            'severity' => $validated['severity'],
            'urutan' => $nextUrutan,
            'status' => 'Aktif',
        ]);

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Item bahaya berhasil ditambahkan.');
    }

    public function updateItem(UpdateItemChecklistRequest $request, ItemChecklist $itemChecklist): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $itemChecklist->load('masterChecklist.lokasi');
        ChecklistTemuanAccess::assertCanManageChecklist($user, $itemChecklist->masterChecklist);

        $itemChecklist->update([
            'nama_item' => $validated['nama_item'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'probability' => $validated['probability'],
            'severity' => $validated['severity'],
        ]);

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Item bahaya berhasil diperbarui.');
    }

    public function destroyItem(Request $request, ItemChecklist $itemChecklist): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && ChecklistTemuanAccess::canManage($user), 403);

        $itemChecklist->load('masterChecklist.lokasi');
        ChecklistTemuanAccess::assertCanManageChecklist($user, $itemChecklist->masterChecklist);

        try {
            $itemChecklist->delete();
        } catch (QueryException) {
            return redirect()
                ->route('inventaris.checklist-temuan')
                ->with('error', 'Item tidak dapat dihapus karena masih terhubung ke data inspeksi.');
        }

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Item bahaya berhasil dihapus.');
    }

    public function toggleItemStatus(Request $request, ItemChecklist $itemChecklist): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && ChecklistTemuanAccess::canManage($user), 403);

        $itemChecklist->load('masterChecklist.lokasi');
        ChecklistTemuanAccess::assertCanManageChecklist($user, $itemChecklist->masterChecklist);

        $itemChecklist->update([
            'status' => $itemChecklist->status === 'Aktif' ? 'Nonaktif' : 'Aktif',
        ]);

        return redirect()
            ->route('inventaris.checklist-temuan')
            ->with('success', 'Status item diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeChecklist(MasterChecklist $masterChecklist): array
    {
        return [
            'id' => $masterChecklist->id,
            'namaChecklist' => $masterChecklist->nama_checklist,
            'lokasiId' => $masterChecklist->lokasi_id,
            'lokasi' => $masterChecklist->lokasi?->nama_lokasi ?? '-',
            'status' => $masterChecklist->status,
            'expanded' => true,
            'items' => $masterChecklist->items->map(fn (ItemChecklist $item) => [
                'id' => $item->id,
                'namaItem' => $item->nama_item,
                'deskripsi' => $item->deskripsi ?? '',
                'probability' => (int) $item->probability,
                'severity' => (int) $item->severity,
                'aktif' => $item->status === 'Aktif',
            ])->values()->all(),
        ];
    }
}
