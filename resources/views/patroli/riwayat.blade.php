@extends('layouts.app')

@section('title', 'Patroli - Safety Patrol K3LH')
@section('page_title', 'Patroli')

@section('content')
    <div class="space-y-4"
        x-data="patroliRiwayatPage({{ \Illuminate\Support\Js::from([
            'overview' => $overview,
            'periodeOptions' => $periodeOptions,
            'storeChecklistUrl' => $storeChecklistUrl,
            'storeItemUrlTemplate' => $storeItemUrlTemplate,
            'toggleItemUrlTemplate' => $toggleItemUrlTemplate,
        ]) }})">
        @if (session('patroli_success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('patroli_success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Patroli</h1>
                <p class="mt-0.5 text-sm text-gray-500">Pantau progress inspeksi temuan bahaya dan pemeriksaan APAR per caturwulan</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a x-bind:href="overview.temuan.scan_url" x-show="overview.temuan.can_modify" x-cloak
                    class="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    <x-icon name="qr-code" class="h-4 w-4" />
                    Scan Lokasi
                </a>
                <a x-bind:href="overview.apar.scan_url" x-show="overview.apar.can_modify" x-cloak
                    class="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    <x-icon name="qr-code" class="h-4 w-4" />
                    Scan APAR
                </a>
                <p class="text-xs text-gray-500" x-show="!overview.temuan.can_modify && !overview.apar.can_modify" x-cloak>
                    Periode ini sudah selesai, scan tidak tersedia.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-xs">
            <span class="font-semibold text-gray-600">Legenda:</span>
            <span class="inline-flex items-center gap-1.5 rounded-md border-2 border-emerald-400 bg-emerald-50 px-2 py-1 font-medium text-emerald-800">
                <span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> Sudah dicek
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-md border-2 border-red-400 bg-red-50 px-2 py-1 font-medium text-red-800">
                <span class="h-2.5 w-2.5 rounded-sm bg-red-500"></span> Belum dicek
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-md border-2 border-amber-400 bg-amber-50 px-2 py-1 font-medium text-amber-900">
                <span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span> Perlu checklist / item
            </span>
        </div>

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-content class="space-y-4 p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex flex-col gap-1.5 sm:flex-row sm:items-end sm:gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Periode</label>
                            <select class="h-10 min-w-[260px] rounded-md border border-gray-200 bg-white px-3 text-sm"
                                x-on:change="changePeriode($event.target.value)">
                                @foreach ($periodeOptions as $opt)
                                    <option value="{{ $opt['value'] }}" @selected($opt['value'] === $overview['periode'])>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <select class="h-9 rounded-md border border-gray-200 bg-white px-3 text-sm" x-model="sectionFilter">
                            <option value="semua">Semua</option>
                            <option value="temuan">Temuan Bahaya</option>
                            <option value="apar">APAR</option>
                        </select>
                        <select class="h-9 rounded-md border border-gray-200 bg-white px-3 text-sm" x-model="filterStatus">
                            <option value="semua">Semua status</option>
                            <option value="selesai">Sudah dicek</option>
                            <option value="belum">Belum dicek</option>
                            <option value="persiapan">Perlu checklist / item</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-orange-100 bg-orange-50/40 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-semibold text-orange-800">Temuan Bahaya</span>
                            <span class="text-xs text-orange-700"
                                x-text="overview.temuan.progress.selesai + '/' + overview.temuan.progress.total + ' lokasi'"></span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/80">
                            <div class="h-full rounded-full bg-emerald-500 transition-all"
                                x-bind:style="`width: ${overview.temuan.progress.persen}%`"></div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <a x-bind:href="overview.temuan.view_inspeksi_url" x-show="overview.temuan.view_inspeksi_url" x-cloak
                                class="inline-flex h-8 items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                <x-icon name="eye" class="h-3.5 w-3.5" />
                                Lihat Terinspeksi
                            </a>
                            <template x-if="overview.temuan.can_modify">
                                <div class="flex flex-wrap gap-2">
                            <button type="button" class="inline-flex h-8 items-center rounded-md bg-white px-2.5 text-xs font-medium text-gray-700 hover:bg-white/80"
                                x-on:click="openChecklistModal()">+ Checklist</button>
                            <button type="button" class="inline-flex h-8 items-center rounded-md bg-white px-2.5 text-xs font-medium text-gray-700 hover:bg-white/80"
                                x-on:click="openItemModal()">+ Item</button>
                            <button type="button" class="inline-flex h-8 items-center rounded-md border border-blue-200 bg-blue-50 px-2.5 text-xs font-medium text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                                x-on:click="tandaiSelesai('temuan')" x-bind:disabled="finishingTemuan">
                                Selesai
                            </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="rounded-lg border border-blue-100 bg-blue-50/40 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-semibold text-blue-800">Pemantauan APAR</span>
                            <span class="text-xs text-blue-700"
                                x-text="overview.apar.progress.selesai + '/' + overview.apar.progress.total + ' unit'"></span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/80">
                            <div class="h-full rounded-full bg-emerald-500 transition-all"
                                x-bind:style="`width: ${overview.apar.progress.persen}%`"></div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <a x-bind:href="overview.apar.view_apar_url" x-show="overview.apar.view_apar_url" x-cloak
                                class="inline-flex h-8 items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                <x-icon name="eye" class="h-3.5 w-3.5" />
                                Lihat Terperiksa
                            </a>
                            <button type="button" class="inline-flex h-8 items-center rounded-md border border-blue-200 bg-blue-50 px-2.5 text-xs font-medium text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                                x-show="overview.apar.can_modify"
                                x-on:click="tandaiSelesai('apar')" x-bind:disabled="finishingApar">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <x-ui.input type="search" placeholder="Cari lokasi, checklist, kode APAR..."
                        class="h-10 w-full bg-white pl-9" x-model="q" />
                </div>
                <p class="text-xs text-gray-400" x-text="overview.rentang_tanggal"></p>
            </x-ui.card-content>
        </x-ui.card>

        {{-- Lokasi belum punya checklist / item — dipisah dari grid patroli utama --}}
        <section class="space-y-3"
            x-show="(sectionFilter === 'semua' || sectionFilter === 'temuan') && filteredTemuanBelumSiap().length > 0">
            <div>
                <h2 class="text-sm font-bold text-gray-800">Perlu Persiapan Checklist</h2>
                <p class="mt-0.5 text-xs text-gray-500">Lokasi di bawah belum memiliki checklist atau item temuan bahaya. Lengkapi hanya jika lokasi tersebut akan diinspeksi pada periode ini.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="row in filteredTemuanBelumSiap()" :key="'p-' + row.lokasi_id">
                    <div class="flex flex-col rounded-xl border-2 border-amber-400 bg-amber-50/80 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-gray-900" x-text="row.nama"></p>
                                <p class="mt-0.5 truncate text-[11px] text-gray-600"
                                    x-text="row.nama_checklist || 'Belum ada checklist'"></p>
                            </div>
                            <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase bg-amber-600 text-white"
                                x-text="row.status_label"></span>
                        </div>
                        <p class="mt-2 text-[11px] text-amber-900" x-text="row.belum_siap_jenis === 'checklist'
                            ? 'Belum ada checklist untuk lokasi ini.'
                            : 'Checklist sudah ada. Tambahkan minimal satu item temuan bahaya bila lokasi akan diinspeksi.'"></p>
                        <div class="mt-3 flex gap-2" x-show="overview.temuan.can_modify && row.belum_siap_jenis === 'item'">
                            <button type="button" class="flex-1 rounded-md border border-amber-400 bg-white px-2 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100"
                                x-on:click="openItemModal(row.checklist_id)">Tambah item</button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <section class="space-y-3" x-show="sectionFilter === 'semua' || sectionFilter === 'temuan'">
            <h2 class="text-sm font-bold text-gray-800">Temuan Bahaya: Lokasi Patroli</h2>
            <p class="text-xs text-gray-500">Lokasi dengan checklist lengkap, siap diinspeksi atau sudah dicek.</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="row in filteredTemuanSiap()" :key="'t-' + row.lokasi_id">
                    <div class="flex flex-col rounded-xl border-2 p-3 transition-shadow hover:shadow-md"
                        x-bind:class="cardClass(row.status)">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-gray-900" x-text="row.nama"></p>
                                <p class="mt-0.5 truncate text-[11px] text-gray-600" x-text="row.nama_checklist || 'Belum ada checklist'"></p>
                            </div>
                            <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                x-bind:class="row.status === 'selesai' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
                                x-text="row.status === 'selesai' ? 'Sudah' : 'Belum'"></span>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500">
                            <span x-show="row.status === 'selesai'" x-text="row.tanggal + ' · ' + row.item_count + ' item'"></span>
                            <span x-show="row.status === 'belum' && row.checklist_live && row.item_count > 0"
                                x-text="row.item_count + ' item aktif · siap diinspeksi'"></span>
                            <span x-show="row.status === 'belum' && row.checklist_live && row.item_count === 0"
                                class="text-amber-700">Semua item nonaktif. Aktifkan item untuk inspeksi</span>
                            <span x-show="row.status === 'belum' && !row.checklist_live"
                                class="text-gray-500">Tidak diinspeksi pada periode ini</span>
                            <span x-show="row.item_tidak_sesuai > 0" class="text-red-600 font-semibold"
                                x-text="' · ' + row.item_tidak_sesuai + ' temuan'"></span>
                        </p>
                        <div class="mt-3">
                            <button type="button"
                                class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                x-on:click="toggleDetail('t-' + row.lokasi_id)"
                                x-text="expandedIds.includes('t-' + row.lokasi_id) ? 'Tutup Item' : 'Lihat Item'"></button>
                        </div>
                        <div class="mt-2 space-y-2 border-t border-black/5 pt-2" x-show="expandedIds.includes('t-' + row.lokasi_id)">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Item Temuan Bahaya</p>
                            <template x-for="item in (row.checklist_items || [])" :key="item.id">
                                <div class="flex items-start justify-between gap-2 rounded-md bg-white/70 px-2 py-1.5 text-xs"
                                    x-bind:class="!item.aktif ? 'opacity-60' : ''">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-gray-800" x-text="item.nama_item"></p>
                                        <p class="mt-0.5 text-[10px] text-gray-500">
                                            <span x-text="item.level_risiko"></span>
                                            <span x-show="row.status === 'selesai' && item.hasil_inspeksi"
                                                x-bind:class="item.hasil_inspeksi === 'Ya' ? 'text-emerald-600' : 'text-red-600'"
                                                x-text="' · ' + (item.hasil_inspeksi === 'Ya' ? 'Sesuai' : 'Tidak sesuai')"></span>
                                        </p>
                                    </div>
                                    <button type="button"
                                        class="shrink-0 rounded px-2 py-1 text-[10px] font-semibold transition-colors"
                                        x-bind:class="item.aktif ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'"
                                        x-show="row.checklist_live && overview.temuan.can_modify"
                                        x-bind:disabled="togglingItemId === item.id"
                                        x-on:click="toggleChecklistItem(row, item)"
                                        x-text="item.aktif ? 'Aktif' : 'Nonaktif'"></button>
                                </div>
                            </template>
                            <p class="text-[11px] text-gray-400"
                                x-show="!(row.checklist_items || []).length && row.status === 'selesai'">
                                Tidak ada item tersimpan untuk inspeksi ini.
                            </p>
                            <p class="text-[11px] text-gray-400"
                                x-show="!(row.checklist_items || []).length && row.status === 'belum' && row.checklist_live">
                                Tidak ada item.
                            </p>
                        </div>
                    </div>
                </template>
            </div>
            <p class="text-center text-sm text-gray-500" x-show="filteredTemuanSiap().length === 0 && filteredTemuanBelumSiap().length === 0">Tidak ada lokasi temuan yang cocok.</p>
            <p class="text-center text-sm text-gray-500" x-show="filteredTemuanSiap().length === 0 && filteredTemuanBelumSiap().length > 0">Belum ada lokasi siap patroli. Selesaikan persiapan checklist di atas.</p>
        </section>

        <section class="space-y-3" x-show="sectionFilter === 'semua' || sectionFilter === 'apar'">
            <h2 class="text-sm font-bold text-gray-800">Pemantauan APAR: Unit</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <template x-for="row in filteredApar()" :key="'a-' + row.apar_id">
                    <div class="flex flex-col rounded-xl border-2 p-3 transition-shadow hover:shadow-md"
                        x-bind:class="cardClass(row.status)">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-gray-900" x-text="row.kode_apar"></p>
                                <p class="mt-0.5 truncate text-[11px] text-gray-600" x-text="row.lokasi"></p>
                            </div>
                            <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                x-bind:class="row.status === 'selesai' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
                                x-text="row.status === 'selesai' ? 'Sudah' : 'Belum'"></span>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500">
                            <span x-text="row.jenis_kapasitas"></span>
                            <span x-show="row.status === 'selesai'" x-text="' · ' + row.tanggal"></span>
                        </p>
                    </div>
                </template>
            </div>
            <p class="text-center text-sm text-gray-500" x-show="filteredApar().length === 0">Tidak ada APAR yang cocok.</p>
        </section>

        @include('patroli.partials.riwayat-modals')
    </div>
@endsection
