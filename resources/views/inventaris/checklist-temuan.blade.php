@extends('layouts.app')

@section('title', 'Kelola Checklist Temuan - Safety Patrol K3LH')
@section('page_title', 'Kelola Checklist Temuan')

@php
    use Illuminate\Support\Js;

    $kelolaConfig = [
        'checklists' => $checklists,
        'lokasiOptions' => $lokasiOptions,
        'roleScope' => $roleScope,
        'canCreate' => $canCreate,
        'urls' => [
            'storeChecklist' => route('inventaris.checklist-temuan.store'),
            'updateChecklist' => url('/inventaris/checklist-temuan'),
            'destroyChecklist' => url('/inventaris/checklist-temuan'),
            'toggleChecklist' => url('/inventaris/checklist-temuan'),
            'storeItem' => url('/inventaris/checklist-temuan'),
            'updateItem' => url('/inventaris/checklist-temuan/items'),
            'destroyItem' => url('/inventaris/checklist-temuan/items'),
            'toggleItem' => url('/inventaris/checklist-temuan/items'),
        ],
        'oldChecklist' => [
            'nama' => old('nama_checklist', ''),
            'lokasiId' => old('lokasi_id', ''),
        ],
        'oldItem' => [
            'namaItem' => old('nama_item', ''),
            'deskripsi' => old('deskripsi', ''),
            'probability' => (int) old('probability', 0),
            'severity' => (int) old('severity', 0),
        ],
    ];
@endphp

@section('content')
    <div class="max-w-4xl mx-auto space-y-5 pb-10"
        x-data="kelolaChecklistTemuan({{ Js::from($kelolaConfig) }})">

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <a href="{{ route('dashboard') }}" class="shrink-0 mt-0.5">
                    <x-ui.button variant="ghost" size="icon" aria-label="Kembali">
                        <x-icon name="arrow-left" class="w-5 h-5" />
                    </x-ui.button>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Kelola Checklist Temuan Bahaya</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        @if ($roleScope === 'petugas')
                            Kelola checklist temuan untuk gedung dan ruangan (seluruh lokasi kecuali laboratorium).
                        @else
                            Kelola checklist temuan bahaya untuk laboratorium yang menjadi tanggung jawab Anda.
                        @endif
                    </p>
                </div>
            </div>
            <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white text-sm gap-2 self-start sm:self-auto"
                x-show="canCreate" x-on:click="openChecklistModal(null)">
                <x-icon name="plus" class="w-4 h-4" />
                Buat Checklist Baru
            </x-ui.button>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shrink-0">
                    <x-icon name="clipboard-list" class="w-[18px] h-[18px] text-blue-600" />
                </div>
                <div>
                    <p class="text-xl font-black text-gray-900 leading-none" x-text="checklists.length"></p>
                    <p class="text-[11px] text-gray-500 mt-0.5">Total Checklist</p>
                </div>
            </div>
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shrink-0">
                    <x-icon name="shield-check" class="w-[18px] h-[18px] text-emerald-600" />
                </div>
                <div>
                    <p class="text-xl font-black text-gray-900 leading-none" x-text="totalAktif()"></p>
                    <p class="text-[11px] text-gray-500 mt-0.5">Checklist Aktif</p>
                </div>
            </div>
            <div class="bg-orange-50 border border-orange-100 rounded-xl p-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shrink-0">
                    <x-icon name="alert-triangle" class="w-[18px] h-[18px] text-orange-600" />
                </div>
                <div>
                    <p class="text-xl font-black text-gray-900 leading-none" x-text="totalItemAktif()"></p>
                    <p class="text-[11px] text-gray-500 mt-0.5">Item Bahaya Aktif</p>
                </div>
            </div>
        </div>

        <div class="flex items-start gap-2.5 text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3.5 py-2.5">
            <x-icon name="info" class="w-3.5 h-3.5 mt-0.5 shrink-0" />
            <span>
                Nilai <strong>P</strong> (Probability) dan <strong>S</strong> (Severity) yang Anda tetapkan di sini akan digunakan sistem untuk menghitung tingkat risiko secara otomatis saat petugas melakukan inspeksi. Petugas tidak dapat mengubah bobot ini di lapangan.
            </span>
        </div>

        <template x-if="checklists.length === 0">
            <div class="text-center py-16 text-gray-400">
                <x-icon name="clipboard-list" class="w-12 h-12 mx-auto mb-3 opacity-30" />
                <p class="font-semibold">Belum ada checklist</p>
                <p class="text-sm mt-1" x-show="canCreate">Mulai dengan membuat checklist baru.</p>
                <p class="text-sm mt-1" x-show="!canCreate">Belum ada lokasi yang dapat dikelola. Tambahkan lokasi gedung/ruangan terlebih dahulu.</p>
            </div>
        </template>

        <template x-for="checklist in checklists" :key="checklist.id">
            <div class="rounded-lg border shadow-sm overflow-hidden transition-all"
                :class="checklist.status === 'Nonaktif' ? 'border-gray-200 opacity-70 bg-white' : 'border-gray-200 bg-white'">
                <div class="py-0 px-0">
                    <div class="flex items-center gap-0">
                        <div class="w-1 self-stretch rounded-l-lg shrink-0"
                            :class="checklist.status === 'Aktif' ? 'bg-emerald-400' : 'bg-gray-300'"></div>
                        <div class="flex-1 px-4 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0 cursor-pointer" x-on:click="toggleExpand(checklist.id)">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-bold text-gray-900" x-text="checklist.namaChecklist"></h3>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0 text-[10px] font-semibold h-5"
                                            :class="checklist.status === 'Aktif'
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                : 'bg-gray-100 text-gray-500 border-gray-300'"
                                            x-text="checklist.status"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <x-icon name="map-pin" class="w-3 h-3 text-gray-400 shrink-0" />
                                        <span class="text-[11px] text-gray-500 truncate" x-text="checklist.lokasi"></span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                                        <span class="text-[11px] text-gray-500">
                                            <strong class="text-gray-700" x-text="checklist.items.length"></strong> item total
                                            <span> · </span>
                                            <strong class="text-emerald-600"
                                                x-text="checklist.items.filter(i => i.aktif).length"></strong> aktif
                                        </span>
                                        <template x-if="risikoTinggiCount(checklist.items) > 0">
                                            <span class="text-[11px] text-orange-600">
                                                <strong x-text="risikoTinggiCount(checklist.items)"></strong> item risiko tinggi
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" x-on:click.stop="toggleChecklistStatus(checklist.id)"
                                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border transition-all"
                                        :class="checklist.status === 'Aktif'
                                            ? 'text-emerald-700 border-emerald-200 bg-emerald-50 hover:bg-emerald-100'
                                            : 'text-gray-500 border-gray-200 bg-gray-100 hover:bg-gray-200'">
                                        <span class="inline-flex items-center gap-1.5" x-show="checklist.status === 'Aktif'">
                                            <x-icon name="toggle-right" class="w-3.5 h-3.5" /> Aktif
                                        </span>
                                        <span class="inline-flex items-center gap-1.5" x-show="checklist.status !== 'Aktif'">
                                            <x-icon name="toggle-left" class="w-3.5 h-3.5" /> Nonaktif
                                        </span>
                                    </button>
                                    <button type="button" x-on:click.stop="openChecklistModal(checklist)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit checklist">
                                        <x-icon name="pencil" class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" x-on:click.stop="deleteChecklist(checklist.id)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                        title="Hapus checklist">
                                        <x-icon name="trash" class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" x-on:click.stop="toggleExpand(checklist.id)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors">
                                        <x-icon name="chevron-up" class="w-4 h-4" x-show="checklist.expanded" />
                                        <x-icon name="chevron-down" class="w-4 h-4" x-show="!checklist.expanded" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-4 pt-0 space-y-2 bg-gray-50/50 border-t border-gray-100" x-show="checklist.expanded">
                    <div class="flex items-center justify-between pt-3 pb-1 gap-2 flex-wrap">
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">
                            <span x-text="checklist.items.length"></span> Item Bahaya
                        </span>
                        <div class="flex items-center gap-3 text-[10px] text-gray-400">
                            <span>P = Probability</span>
                            <span>S = Severity</span>
                            <span>R = P × S</span>
                        </div>
                    </div>

                    <template x-if="checklist.items.length === 0">
                        <div class="text-center py-6 text-sm text-gray-400 border border-dashed border-gray-200 rounded-xl">
                            Belum ada item. Tambahkan item bahaya pertama untuk checklist ini.
                        </div>
                    </template>

                    <template x-for="item in checklist.items" :key="item.id">
                        <div class="flex items-center gap-3 px-4 py-3 border rounded-xl transition-all"
                            :class="item.aktif ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-200 opacity-60'">
                            <x-icon name="grip-vertical" class="w-4 h-4 text-gray-300 shrink-0 cursor-grab" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold leading-snug"
                                    :class="item.aktif ? 'text-gray-800' : 'text-gray-500 line-through'"
                                    x-text="item.namaItem"></p>
                                <p class="text-[11px] text-gray-400 mt-0.5 truncate" x-show="item.deskripsi"
                                    x-text="item.deskripsi"></p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <div class="text-center">
                                    <div class="text-[10px] text-gray-400 leading-none mb-0.5">P</div>
                                    <div
                                        class="w-7 h-7 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-sm font-bold text-blue-700">
                                        <span x-text="item.probability"></span>
                                    </div>
                                </div>
                                <span class="text-gray-400 text-xs mt-3">×</span>
                                <div class="text-center">
                                    <div class="text-[10px] text-gray-400 leading-none mb-0.5">S</div>
                                    <div
                                        class="w-7 h-7 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-sm font-bold text-blue-700">
                                        <span x-text="item.severity"></span>
                                    </div>
                                </div>
                                <span class="text-gray-400 text-xs mt-3">=</span>
                                <div class="text-center">
                                    <div class="text-[10px] text-gray-400 leading-none mb-0.5">R</div>
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-sm font-bold border"
                                        :class="[itemStyle(item).barBg, itemStyle(item).text, itemStyle(item).borderR]">
                                        <span x-text="itemSkor(item)"></span>
                                    </div>
                                </div>
                            </div>
                            <span
                                class="hidden sm:inline-flex items-center rounded-full border px-2 py-0 text-[10px] font-semibold shrink-0"
                                :class="itemStyle(item).badge" x-text="itemLevel(item)"></span>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" x-on:click="toggleItem(checklist.id, item.id)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg transition-colors"
                                    :class="item.aktif ? 'text-emerald-500 hover:bg-emerald-50' : 'text-gray-400 hover:bg-gray-100'"
                                    :title="item.aktif ? 'Nonaktifkan item' : 'Aktifkan item'">
                                    <x-icon name="toggle-right" class="w-4 h-4" x-show="item.aktif" />
                                    <x-icon name="toggle-left" class="w-4 h-4" x-show="!item.aktif" />
                                </button>
                                <button type="button" x-on:click="openItemModal(checklist.id, item)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="Edit item">
                                    <x-icon name="pencil" class="w-3.5 h-3.5" />
                                </button>
                                <button type="button" x-on:click="deleteItem(checklist.id, item.id)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                    title="Hapus item">
                                    <x-icon name="trash" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <button type="button" x-on:click="openItemModal(checklist.id, null)"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-dashed border-gray-300 text-xs font-semibold text-gray-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50 transition-all mt-2">
                        <x-icon name="plus" class="w-3.5 h-3.5" />
                        Tambah Item Bahaya
                    </button>
                </div>
            </div>
        </template>

        <button type="button" x-show="canCreate" x-on:click="openChecklistModal(null)"
            class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl border-2 border-dashed border-gray-300 text-sm font-semibold text-gray-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50 transition-all">
            <x-icon name="plus" class="w-4 h-4" />
            Buat Checklist Baru
        </button>

        {{-- Modal Checklist --}}
        <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-show="showChecklistModal" x-cloak x-on:keydown.escape.window="closeChecklistModal()">
            <div class="fixed inset-0" x-on:click="closeChecklistModal()" aria-hidden="true"></div>
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden relative"
                x-on:click.stop>
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-base font-bold text-gray-900"
                            x-text="editingChecklist?.id ? 'Edit Checklist' : 'Buat Checklist Baru'"></h2>
                        <p class="text-xs text-gray-500 mt-0.5">Checklist akan dikaitkan dengan QR Code lokasi</p>
                    </div>
                    <button type="button" x-on:click="closeChecklistModal()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100">
                        <x-icon name="x" class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <x-ui.label class="text-xs font-semibold text-gray-700 mb-1.5 block">
                            Nama Checklist <span class="text-red-500">*</span>
                        </x-ui.label>
                        <x-ui.input type="text" name="nama_checklist" x-model="checklistForm.nama"
                            placeholder="Contoh: Checklist Gedung A 2026" class="text-sm h-10 bg-white" maxlength="100" />
                    </div>
                    <div>
                        <x-ui.label class="text-xs font-semibold text-gray-700 mb-1.5 block">
                            Lokasi / Unit <span class="text-red-500">*</span>
                        </x-ui.label>
                        <select name="lokasi_id" x-model.number="checklistForm.lokasiId" required
                            class="flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                            <option value="">Pilih lokasi yang akan diawasi...</option>
                            <template x-for="opt in lokasiOptions" :key="opt.id">
                                <option :value="opt.id" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex items-start gap-2 text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2.5">
                        <x-icon name="info" class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                        <span x-show="roleScope === 'petugas'">Hanya gedung dan ruangan. Laboratorium dikelola Kalab masing-masing.</span>
                        <span x-show="roleScope === 'kalab'">Checklist muncul saat QR laboratorium Anda dipindai.</span>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-100 flex gap-2">
                    <x-ui.button variant="outline" class="flex-1 text-sm" x-on:click="closeChecklistModal()">Batal</x-ui.button>
                    <x-ui.button class="flex-1 text-sm bg-blue-600 hover:bg-blue-700 text-white gap-2"
                        x-bind:disabled="!checklistForm.nama?.trim() || !checklistForm.lokasiId"
                        x-on:click="saveChecklist()">
                        <x-icon name="save" class="w-4 h-4" />
                        <span x-text="editingChecklist?.id ? 'Simpan Perubahan' : 'Buat Checklist'"></span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        {{-- Modal Item --}}
        <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
            x-show="showItemModal" x-cloak x-on:keydown.escape.window="closeItemModal()">
            <div class="fixed inset-0" x-on:click="closeItemModal()" aria-hidden="true"></div>
            <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden max-h-[95vh] flex flex-col relative"
                x-on:click.stop>
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                    <div>
                        <h2 class="text-base font-bold text-gray-900"
                            x-text="editingItem?.item?.id ? 'Edit Item Bahaya' : 'Tambah Item Bahaya'"></h2>
                        <p class="text-xs text-gray-500 mt-0.5">Tetapkan bobot risiko sesuai kondisi laboratorium Anda</p>
                    </div>
                    <button type="button" x-on:click="closeItemModal()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100">
                        <x-icon name="x" class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-5 space-y-5 overflow-y-auto flex-1 min-h-0">
                    <div>
                        <x-ui.label class="text-xs font-semibold text-gray-700 mb-1.5 block">
                            Nama Item Bahaya <span class="text-red-500">*</span>
                        </x-ui.label>
                        <x-ui.input type="text" x-model="itemForm.namaItem"
                            placeholder="Contoh: Kondisi kabel instalasi listrik" class="text-sm h-10 bg-white" />
                    </div>
                    <div>
                        <x-ui.label class="text-xs font-semibold text-gray-700 mb-1.5 block">
                            Deskripsi & Kriteria Penilaian
                            <span class="font-normal text-gray-400 ml-1">(opsional)</span>
                        </x-ui.label>
                        <x-ui.textarea rows="2" x-model="itemForm.deskripsi"
                            placeholder="Jelaskan apa yang perlu diperiksa dan bagaimana menilainya..." class="text-sm resize-none"></x-ui.textarea>
                    </div>
                    <div>
                        <x-ui.label class="text-xs font-semibold text-gray-700 mb-1.5 block">
                            Probability (P): kemungkinan bahaya terjadi <span class="text-red-500">*</span>
                        </x-ui.label>
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="val in [1,2,3,4,5]" :key="'p'+val">
                                <button type="button" x-on:click="itemForm.probability = val"
                                    class="flex flex-col items-center gap-1 py-2.5 px-1 rounded-xl border-2 text-center transition-all"
                                    :class="itemForm.probability === val ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-200 hover:bg-blue-50/50'">
                                    <span class="text-lg font-bold leading-none"
                                        :class="itemForm.probability === val ? 'text-blue-700' : 'text-gray-600'"
                                        x-text="val"></span>
                                    <span class="text-[9px] leading-tight"
                                        :class="itemForm.probability === val ? 'text-blue-600' : 'text-gray-400'"
                                        x-text="pLabel[val]"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <x-ui.label class="text-xs font-semibold text-gray-700 mb-1.5 block">
                            Severity (S): keparahan dampak jika terjadi <span class="text-red-500">*</span>
                        </x-ui.label>
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="val in [1,2,3,4,5]" :key="'s'+val">
                                <button type="button" x-on:click="itemForm.severity = val"
                                    class="flex flex-col items-center gap-1 py-2.5 px-1 rounded-xl border-2 text-center transition-all"
                                    :class="itemForm.severity === val ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-200 hover:bg-blue-50/50'">
                                    <span class="text-lg font-bold leading-none"
                                        :class="itemForm.severity === val ? 'text-blue-700' : 'text-gray-600'"
                                        x-text="val"></span>
                                    <span class="text-[9px] leading-tight"
                                        :class="itemForm.severity === val ? 'text-blue-600' : 'text-gray-400'"
                                        x-text="sLabel[val]"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="rounded-xl border px-4 py-3" x-show="modalItemSkor() > 0 && modalItemLevel() && modalItemStyle()"
                        :class="modalItemStyle()?.badge">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold opacity-80 mb-0.5">Preview Risiko Otomatis</p>
                                <p class="text-xl font-black">
                                    <span x-text="itemForm.probability"></span> × <span x-text="itemForm.severity"></span> =
                                    <span x-text="modalItemSkor()"></span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs opacity-70 mb-0.5">Level Risiko</p>
                                <p class="text-base font-bold" x-text="modalItemLevel()"></p>
                            </div>
                        </div>
                        <div class="mt-2.5 w-full h-1.5 bg-white/50 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="modalItemStyle()?.bar"
                                :style="'width:' + (modalItemSkor() / 25) * 100 + '%'"></div>
                        </div>
                        <p class="text-[10px] mt-1.5 opacity-60">
                            Dihitung otomatis saat petugas menandai item ini Tidak Sesuai
                        </p>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-100 flex gap-2 shrink-0">
                    <x-ui.button variant="outline" class="flex-1 text-sm" x-on:click="closeItemModal()">Batal</x-ui.button>
                    <x-ui.button class="flex-1 text-sm bg-blue-600 hover:bg-blue-700 text-white gap-2"
                        x-bind:disabled="!itemForm.namaItem?.trim() || itemForm.probability === 0 || itemForm.severity === 0"
                        x-on:click="saveItem()">
                        <x-icon name="save" class="w-4 h-4" />
                        Simpan Item
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
@endsection
