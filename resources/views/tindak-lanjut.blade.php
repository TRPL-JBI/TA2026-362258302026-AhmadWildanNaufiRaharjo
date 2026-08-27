@extends('layouts.app')

@section('title', 'Tindak Lanjut - Safety Patrol K3LH')
@section('page_title', 'Tindak Lanjut')

@php
    use Illuminate\Support\Js;
    /** @var array<int, array<string, mixed>> $items */
    $items = $items ?? [];
    $periode = $periode ?? '';
    $periodeLabel = $periodeLabel ?? '';
    $periodeRentang = $periodeRentang ?? '';
    $periodeOptions = $periodeOptions ?? [];
    $periodeState = $periodeState ?? ['status' => 'Berlangsung', 'total' => 0, 'selesai' => 0, 'dalam_proses' => 0, 'menunggu' => 0, 'can_finish' => false, 'is_locked' => false];
    $finishPeriodeUrl = $finishPeriodeUrl ?? '';
@endphp

@section('content')
    <div class="space-y-4" x-data="tindakLanjutPage({{ Js::from($items) }}, {
        periode: @js($periode),
        periodeLabel: @js($periodeLabel),
        periodeRentang: @js($periodeRentang),
        periodeOptions: @js($periodeOptions),
        periodeState: @js($periodeState),
        finishPeriodeUrl: @js($finishPeriodeUrl),
        updateInspeksiUrl: @js(route('tindak-lanjut.inspeksi.update', ['detailInspeksi' => '__ID__'])),
        updateInsidenUrl: @js(route('tindak-lanjut.insiden.update', ['laporanInsiden' => '__ID__'])),
        csrf: @js(csrf_token()),
    })">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}">
                <x-ui.button variant="ghost" size="icon" aria-label="Kembali">
                    <x-icon name="arrow-left" class="w-5 h-5" />
                </x-ui.button>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Tindak Lanjut</h1>
                <p class="text-sm text-gray-500 mt-0.5">Kelola temuan prioritas tinggi dan laporan insiden per caturwulan</p>
            </div>
        </div>

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-content class="p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Periode</label>
                        <select class="h-10 min-w-[260px] rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900"
                            x-on:change="changePeriode($event.target.value)">
                            @foreach ($periodeOptions as $opt)
                                <option value="{{ $opt['value'] }}" @selected($opt['value'] === $periode)>
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500" x-text="periodeRentang"></p>
                    </div>
                    <div class="flex flex-col gap-2 sm:items-end">
                        <p class="text-xs text-gray-600">
                            Progress:
                            <span class="font-semibold text-gray-900" x-text="periodeState.selesai + ' selesai · ' + periodeState.dalam_proses + ' berlangsung · ' + periodeState.menunggu + ' menunggu'"></span>
                        </p>
                        <x-ui.button type="button"
                            class="h-10 bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700"
                            x-show="periodeState.can_finish"
                            x-on:click="tandaiPeriodeSelesai()"
                            x-bind:disabled="finishingPeriode">
                            <span x-show="!finishingPeriode">Tandai Periode Selesai</span>
                            <span x-show="finishingPeriode">Memproses...</span>
                        </x-ui.button>
                        <p x-show="periodeState.is_locked" class="text-xs font-medium text-emerald-700 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">
                            Periode ini sudah ditutup. Laporan rekap tersedia di menu Laporan.
                        </p>
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <x-ui.card class="shadow-sm border border-gray-200">
                <x-ui.card-content class="p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900" x-text="items.length"></p>
                    <p class="text-xs text-gray-500 mt-1">Total Item</p>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card class="shadow-sm border border-gray-200">
                <x-ui.card-content class="p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600" x-text="countByStatus('Menunggu Tindakan')"></p>
                    <p class="text-xs text-gray-500 mt-1">Menunggu</p>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card class="shadow-sm border border-gray-200">
                <x-ui.card-content class="p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600" x-text="countByStatus('Dalam Proses')"></p>
                    <p class="text-xs text-gray-500 mt-1">Dalam Proses</p>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card class="shadow-sm border border-gray-200">
                <x-ui.card-content class="p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600" x-text="countByStatus('Selesai')"></p>
                    <p class="text-xs text-gray-500 mt-1">Selesai</p>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-content class="p-4">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1 min-w-0">
                            <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                            <x-ui.input type="search" placeholder="Cari lokasi, deskripsi, tanggal..."
                                class="pl-9 h-10 bg-white text-gray-900 w-full" x-model="filterSearch" />
                        </div>
                        <select
                            class="w-full sm:w-44 h-10 shrink-0 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            x-model="filterJenis">
                            <option value="semua">Semua Jenis</option>
                            <option value="Temuan Patroli">Temuan Patroli</option>
                            <option value="Laporan Insiden Darurat (Satpam)">Laporan Insiden Darurat (Satpam)</option>
                        </select>
                        <select
                            class="w-full sm:w-48 shrink-0 h-10 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            x-model="filterStatus">
                            <option value="semua">Semua Status</option>
                            <option value="Menunggu Tindakan">Menunggu Tindakan</option>
                            <option value="Dalam Proses">Dalam Proses</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                        <select
                            class="w-full sm:w-44 shrink-0 h-10 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            x-model="filterRisiko">
                            <option value="semua">Semua Risiko</option>
                            <option value="Sangat Tinggi">Sangat Tinggi</option>
                            <option value="Tinggi">Tinggi</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Rendah">Rendah</option>
                            <option value="Darurat">Darurat</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <x-ui.button variant="outline" size="sm" type="button" class="text-xs text-gray-600"
                            x-on:click="clearFilters()" x-bind:disabled="!hasActiveFilters()">
                            Reset filter
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="shadow-sm border border-gray-200">
            <x-ui.card-header class="pb-3 border-b border-gray-100">
                <x-ui.card-title class="text-base">Daftar Tindak Lanjut</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[860px]">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">No</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Jenis</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Lokasi</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Deskripsi</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Risiko</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, i) in paginated()" :key="item.uid || (item.ref_type + '-' + item.ref_id)">
                                <tr class="border-b border-gray-50 hover:bg-gray-50/50"
                                    :class="isSangatTinggiRow(item) ? 'bg-red-50/30' : ''">
                                    <td class="py-3 px-4 text-gray-500" x-text="paginationMeta().from + i"></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                            :class="item.jenis === 'Temuan Patroli'
                                                ? 'bg-orange-50 text-orange-700 border-orange-200'
                                                : 'bg-red-50 text-red-700 border-red-200'"
                                            x-text="item.jenis"></span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 whitespace-nowrap" x-text="item.tanggal_list || item.tanggal"></td>
                                    <td class="py-3 px-4 text-gray-700" x-text="item.lokasi"></td>
                                    <td class="py-3 px-4 text-gray-600 max-w-xs truncate" x-text="item.deskripsi"></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                            :class="riskClass(item.risiko)">
                                            <span x-text="item.risiko"></span>
                                            <span x-show="item.skor != null" x-text="' (' + item.skor + ')'"></span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                            :class="statusClass(item.status)">
                                            <span class="inline-flex shrink-0" x-show="item.status === 'Menunggu Tindakan'">
                                                <x-icon name="clock" class="w-3 h-3" />
                                            </span>
                                            <span x-text="item.status"></span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-ui.button variant="ghost" size="icon" class="h-8 w-8 p-0 text-gray-400 hover:text-blue-600"
                                            type="button" aria-label="Detail" x-on:click="openDetail(item)">
                                            <x-icon name="eye" class="w-4 h-4" />
                                        </x-ui.button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredItems().length === 0">
                                <td colspan="8" class="py-10 px-4 text-center text-sm text-gray-500">
                                    Tidak ada data yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <x-ui.list-pagination />
            </x-ui.card-content>
        </x-ui.card>

        {{-- Modal detail --}}
        <template x-if="selected">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" x-on:click.self="closeDetail()"
                x-on:keydown.escape.window="closeDetail()">
                <x-ui.card class="w-full max-w-2xl shadow-2xl border border-gray-200 max-h-[90vh] overflow-y-auto" x-on:click.stop>
                <x-ui.card-header class="border-b border-gray-100 pb-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <x-ui.card-title class="text-lg text-gray-900">Detail Tindak Lanjut</x-ui.card-title>
                            <p class="text-sm text-gray-500 mt-0.5" x-text="selected.lokasi"></p>
                        </div>
                        <x-ui.button variant="ghost" size="icon" class="h-8 w-8 p-0 text-gray-400 shrink-0" type="button" aria-label="Tutup"
                            x-on:click="closeDetail()">
                            <x-icon name="x" class="w-4 h-4" />
                        </x-ui.button>
                    </div>
                </x-ui.card-header>
                <x-ui.card-content class="p-5 space-y-5">
                    {{-- Detail Temuan Patroli --}}
                    <div class="space-y-5" x-show="selected.ref_type === 'inspeksi'">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-xs text-gray-500">Jenis</p>
                                <span class="inline-flex mt-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-orange-50 text-orange-700 border-orange-200"
                                    x-text="selected.jenis"></span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Tanggal Laporan</p>
                                <p class="text-gray-900 font-medium mt-1" x-text="selected.tanggal"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Periode asal</p>
                                <p class="text-gray-900 font-medium mt-1" x-text="selected.periode_asal_label || '-'"></p>
                                <p x-show="selected.is_carry_over" class="text-xs text-amber-700 mt-0.5">Carry-over dari periode sebelumnya</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Tingkat Risiko</p>
                                <span class="inline-flex mt-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    :class="riskClass(selected.risiko)">
                                    <span x-text="selected.risiko"></span>
                                    <span x-show="selected.skor != null" x-text="' (' + selected.skor + ')'"></span>
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Status Saat Ini</p>
                                <span class="inline-flex mt-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    :class="statusClass(selected.status)" x-text="selected.status"></span>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Item Temuan / Bahaya</p>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg" x-text="selected.deskripsi"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Analisa Risiko</p>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg whitespace-pre-line" x-text="selected.analisa_risiko || '-'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Rekomendasi</p>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg whitespace-pre-line" x-text="selected.rekomendasi || '-'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-2">Foto Dokumentasi Patroli</p>
                            <div class="flex flex-wrap gap-2" x-show="(selected.foto_dokumentasi || []).length > 0">
                                <template x-for="photo in (selected.foto_dokumentasi || [])" :key="photo.id">
                                    <a class="w-20 h-20 block overflow-hidden rounded-lg bg-gray-100 border border-gray-200"
                                        target="_blank" x-bind:href="photo.preview">
                                        <img x-bind:src="photo.preview" alt="Foto dokumentasi patroli"
                                            class="w-full h-full object-cover" />
                                    </a>
                                </template>
                            </div>
                            <div x-show="(selected.foto_dokumentasi || []).length === 0"
                                class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center border border-dashed border-gray-300">
                                <x-icon name="image" class="w-6 h-6 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    {{-- Detail Laporan Insiden Darurat --}}
                    <div class="space-y-5" x-show="selected.ref_type === 'insiden'">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-xs text-gray-500">Jenis</p>
                                <span class="inline-flex mt-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-red-50 text-red-700 border-red-200"
                                    x-text="selected.jenis"></span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Tanggal Kejadian</p>
                                <p class="text-gray-900 font-medium mt-1" x-text="selected.tanggal"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Periode asal</p>
                                <p class="text-gray-900 font-medium mt-1" x-text="selected.periode_asal_label || '-'"></p>
                                <p x-show="selected.is_carry_over" class="text-xs text-amber-700 mt-0.5">Carry-over dari periode sebelumnya</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Prioritas</p>
                                <span class="inline-flex mt-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    :class="riskClass(selected.risiko)" x-text="selected.risiko"></span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Status Saat Ini</p>
                                <span class="inline-flex mt-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    :class="statusClass(selected.status)" x-text="selected.status"></span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Pelapor (Satpam)</p>
                                <p class="text-gray-900 font-medium mt-1" x-text="selected.pelapor || '-'"></p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Jenis Insiden</p>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg" x-text="selected.jenis_insiden || selected.deskripsi"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Lokasi Kejadian</p>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg" x-text="selected.lokasi || '-'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Korban</p>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg" x-text="selected.korban || 'Tidak ada / tidak dilaporkan'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Kronologi Kejadian</p>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg whitespace-pre-line" x-text="selected.kronologi || '-'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-2">Foto TKP / Kondisi</p>
                            <div class="flex flex-wrap gap-2" x-show="(selected.foto_dokumentasi || []).length > 0">
                                <template x-for="photo in (selected.foto_dokumentasi || [])" :key="photo.id">
                                    <a class="w-20 h-20 block overflow-hidden rounded-lg bg-gray-100 border border-gray-200"
                                        target="_blank" x-bind:href="photo.preview">
                                        <img x-bind:src="photo.preview" alt="Foto TKP"
                                            class="w-full h-full object-cover" />
                                    </a>
                                </template>
                            </div>
                            <div x-show="(selected.foto_dokumentasi || []).length === 0"
                                class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center border border-dashed border-gray-300">
                                <x-icon name="image" class="w-6 h-6 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100" />

                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-900">Update Status Perbaikan</h3>
                            <p x-show="periodeState.is_locked"
                                class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] text-gray-600">
                                Periode ditutup — hanya tampilan
                            </p>
                        </div>
                        <div x-show="errorMessage && !periodeState.is_locked"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            <span x-text="errorMessage"></span>
                        </div>
                        <div class="space-y-2">
                            <x-ui.label class="text-xs font-medium text-gray-600">
                                Status Perbaikan
                                <span class="text-red-500" x-show="!periodeState.is_locked">*</span>
                            </x-ui.label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="s in ['Menunggu Tindakan', 'Dalam Proses', 'Selesai']" :key="s">
                                    <label class="flex items-center gap-1.5 px-3 py-2 rounded-lg border text-xs transition-colors"
                                        :class="[
                                            selected.status === s
                                                ? (s === 'Selesai'
                                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-700 font-medium'
                                                    : (s === 'Dalam Proses'
                                                        ? 'border-blue-500 bg-blue-50 text-blue-700 font-medium'
                                                        : 'border-yellow-500 bg-yellow-50 text-yellow-700 font-medium'))
                                                : 'border-gray-200 text-gray-600',
                                            periodeState.is_locked ? 'cursor-default opacity-90' : 'cursor-pointer hover:bg-gray-50',
                                        ]">
                                        <input type="radio" class="sr-only" :name="'status-tl-' + selected.uid" :value="s"
                                            x-model="selected.status"
                                            x-bind:disabled="periodeState.is_locked" />
                                        <span x-text="s"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <x-ui.label class="text-xs font-medium text-gray-600">Tanggal Mulai Perbaikan</x-ui.label>
                                <input type="date"
                                    class="h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900"
                                    x-bind:value="selected.tanggal_mulai || ''" disabled />
                            </div>
                            <div class="space-y-2">
                                <x-ui.label class="text-xs font-medium text-gray-600">Tanggal Selesai</x-ui.label>
                                <input type="date"
                                    class="h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900"
                                    x-bind:value="selected.tanggal_selesai || ''" disabled />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <x-ui.label class="text-xs font-medium text-gray-600">Keterangan Tindak Lanjut</x-ui.label>
                            <x-ui.textarea rows="3"
                                class="text-sm resize-none text-gray-900"
                                x-bind:class="periodeState.is_locked ? 'bg-gray-50' : 'bg-white'"
                                placeholder="Jelaskan tindakan perbaikan yang dilakukan..."
                                x-model="selected.catatan"
                                x-bind:readonly="periodeState.is_locked"
                                x-bind:disabled="periodeState.is_locked"></x-ui.textarea>
                        </div>
                        <div class="space-y-2">
                            <x-ui.label class="text-xs font-medium text-gray-600"
                                x-text="periodeState.is_locked ? 'Foto Bukti Perbaikan' : 'Upload Foto Bukti Perbaikan'"></x-ui.label>
                            <div class="flex flex-wrap items-center gap-2" x-show="!periodeState.is_locked">
                                <x-ui.button variant="outline" size="sm" class="h-8 gap-1.5 text-xs text-gray-600" type="button"
                                    x-on:click="pickFotoBuktiCamera()" x-bind:disabled="isSaving">
                                    <x-icon name="camera" class="h-3.5 w-3.5" />
                                    Ambil Foto
                                </x-ui.button>
                                <x-ui.button variant="outline" size="sm" class="h-8 gap-1.5 text-xs text-gray-600" type="button"
                                    x-on:click="pickFotoBuktiGallery()" x-bind:disabled="isSaving">
                                    <x-icon name="image" class="h-3.5 w-3.5" />
                                    Pilih Galeri
                                </x-ui.button>
                                <p class="text-xs text-gray-500" x-show="selected?.fotoBuktiFile" x-text="selected?.fotoBuktiFile?.name"></p>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2" x-show="(selected.foto_bukti || []).length > 0 || selected?.fotoBuktiPreview">
                                <template x-for="photo in (selected.foto_bukti || [])" :key="photo.id">
                                    <a class="w-20 h-20 block overflow-hidden rounded-lg bg-gray-100 border border-gray-200"
                                        target="_blank" x-bind:href="photo.preview">
                                        <img x-bind:src="photo.preview" alt="Foto bukti perbaikan"
                                            class="w-full h-full object-cover" />
                                    </a>
                                </template>

                                <a x-show="selected?.fotoBuktiPreview && !periodeState.is_locked"
                                    class="w-20 h-20 block overflow-hidden rounded-lg bg-gray-100 border border-dashed border-blue-300"
                                    target="_blank" x-bind:href="selected?.fotoBuktiPreview">
                                    <img x-bind:src="selected?.fotoBuktiPreview" alt="Preview foto bukti"
                                        class="w-full h-full object-cover" />
                                </a>
                            </div>
                            <div x-show="(selected.foto_bukti || []).length === 0 && !selected?.fotoBuktiPreview"
                                class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center border border-dashed border-gray-300">
                                <x-icon name="image" class="w-6 h-6 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2" x-show="!periodeState.is_locked">
                        <x-ui.button variant="outline" class="text-gray-600" type="button" x-on:click="closeDetail()">Batal</x-ui.button>
                        <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white" type="button" x-on:click="saveDetail()"
                            x-bind:disabled="isSaving">
                            Simpan Perubahan
                        </x-ui.button>
                    </div>
                    <div class="flex justify-end gap-3 pt-2" x-show="periodeState.is_locked">
                        <x-ui.button variant="outline" class="text-gray-600" type="button" x-on:click="closeDetail()">Tutup</x-ui.button>
                    </div>
                </x-ui.card-content>
                </x-ui.card>
            </div>
        </template>
    </div>
@endsection
