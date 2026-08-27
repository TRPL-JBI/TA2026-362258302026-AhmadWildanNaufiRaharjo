@extends('layouts.app')

@section('title', 'Pemantauan IPAM - Safety Patrol K3LH')
@section('page_title', 'Pemantauan IPAM')

@php
    use Illuminate\Support\Js;
@endphp

@section('content')
    <div class="space-y-4" x-data="pemantauanIpam({{ Js::from($ipamPageConfig) }})">
        <x-pemantauan.ipam.list />

        {{-- ==================== FORM ==================== --}}
        <template x-if="view === 'form'">
            <div class="max-w-6xl mx-auto space-y-6">
                <div class="flex items-center gap-3">
                    <x-ui.button variant="ghost" size="icon" x-on:click="backToList()" aria-label="Kembali">
                        <x-icon name="arrow-left" class="w-5 h-5" />
                    </x-ui.button>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900"
                            x-text="editingReportId ? 'Edit Laporan Pemantauan IPAM' : 'Buat Laporan Pemantauan IPAM'"></h1>
                        <p class="text-sm text-gray-500">Isi data per unit IPAM dan mingguan</p>
                    </div>
                </div>

                <x-ui.card class="border border-gray-200 shadow-sm">
                    <x-ui.card-header>
                        <x-ui.card-title class="text-base">Periode Bulan</x-ui.card-title>
                    </x-ui.card-header>
                    <x-ui.card-content class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <x-ui.label class="text-gray-700">Bulan</x-ui.label>
                            <select x-model="bulan"
                                class="h-11 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <template x-for="b in bulanOptions" :key="b">
                                    <option :value="b" x-text="b"></option>
                                </template>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <x-ui.label class="text-gray-700">Tahun</x-ui.label>
                            <x-ui.year-picker x-model="tahun" />
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                <template x-for="(unit, idxUnit) in units" :key="unit.unitId">
                    <x-ui.card class="overflow-hidden border-l-4 border-l-blue-400 border border-gray-200 shadow-sm">
                        <x-ui.card-header class="border-b border-gray-100 bg-gray-50/50 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex min-w-0 flex-1 cursor-pointer items-center gap-3"
                                    x-on:click="toggleUnitExpand(idxUnit)">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                        <x-icon name="droplets" class="h-4 w-4 text-blue-600" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <x-ui.card-title class="truncate text-sm font-semibold text-gray-900"
                                            x-text="unitName(unit.unitId)"></x-ui.card-title>
                                        <p class="mt-0.5 text-[11px] text-gray-500">
                                            <span x-text="titikByUnit(unit.unitId).length + ' titik'"></span>
                                            <span class="mx-1">·</span>
                                            <span x-text="unit.mingguList.length + ' minggu'"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <x-ui.button variant="outline" size="sm" class="gap-1 shrink-0" type="button"
                                        x-on:click.stop="tambahMinggu(unit.unitId)">
                                        <x-icon name="plus" class="w-4 h-4" />
                                        <span class="hidden sm:inline">Tambah Minggu</span>
                                    </x-ui.button>
                                    <button type="button"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100"
                                        x-on:click="toggleUnitExpand(idxUnit)" aria-label="Buka tutup unit">
                                        <x-icon name="chevron-up" class="h-4 w-4" x-show="unit.expanded" />
                                        <x-icon name="chevron-down" class="h-4 w-4" x-show="!unit.expanded" />
                                    </button>
                                </div>
                            </div>
                        </x-ui.card-header>

                        <x-ui.card-content class="space-y-4 bg-gray-50/40 p-4" x-show="unit.expanded" x-cloak>
                            <template x-for="(minggu, idxMinggu) in unit.mingguList" :key="`${unit.unitId}-${minggu.mingguKe}`">
                                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                    <div class="flex items-center gap-2 border-b border-gray-100 bg-white px-3 py-2.5">
                                        <div class="flex min-w-0 flex-1 cursor-pointer items-center gap-2"
                                            x-on:click="toggleMingguExpand(idxUnit, idxMinggu)">
                                            <p class="text-sm font-semibold text-gray-700" x-text="`Minggu ${minggu.mingguKe}`"></p>
                                            <p class="text-[11px] text-gray-500">
                                                <span x-text="mingguTitikTerisi(unit.unitId, minggu) + ' titik terisi'"></span>
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <button type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-md text-red-400 hover:bg-red-50 hover:text-red-600"
                                                x-show="unit.mingguList.length > 1"
                                                x-on:click="hapusMinggu(unit.unitId, minggu.mingguKe)"
                                                aria-label="Hapus minggu">
                                                <x-icon name="trash" class="w-4 h-4" />
                                            </button>
                                            <button type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100"
                                                x-on:click="toggleMingguExpand(idxUnit, idxMinggu)"
                                                aria-label="Buka tutup minggu">
                                                <x-icon name="chevron-up" class="h-4 w-4" x-show="minggu.expanded" />
                                                <x-icon name="chevron-down" class="h-4 w-4" x-show="!minggu.expanded" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto p-4" x-show="minggu.expanded" x-cloak>
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="bg-gray-50 border-y border-gray-200 align-middle">
                                                    <th class="text-left px-3 py-2.5 font-medium text-gray-600 align-middle">Titik</th>
                                                    <th class="text-left px-3 py-2.5 font-medium text-gray-600 align-middle">pH</th>
                                                    <th class="text-left px-3 py-2.5 font-medium text-gray-600 align-middle">ALT (cfu/ml)</th>
                                                    <th class="text-left px-3 py-2.5 font-medium text-gray-600 align-middle">Salmonella</th>
                                                    <th class="text-left px-3 py-2.5 font-medium text-gray-600 align-middle">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="titik in titikByUnit(unit.unitId)" :key="titik.id">
                                                    <tr class="border-b border-gray-100 align-middle">
                                                        <td class="px-3 py-2.5 font-medium text-gray-700 align-middle" x-text="titik.nama_titik"></td>
                                                        <td class="px-3 py-2.5 align-middle">
                                                            <x-ui.input type="number" step="0.1"
                                                                class="h-9 w-16 text-sm"
                                                                placeholder="7.0"
                                                                x-model="titikData(minggu, titik.id).ph" />
                                                        </td>
                                                        <td class="px-3 py-2.5 align-middle">
                                                            <x-ui.input type="text"
                                                                class="h-9 min-w-[8.5rem] w-full max-w-[11rem] text-sm"
                                                                placeholder="5,50 x 10²"
                                                                x-model="titikData(minggu, titik.id).alt"
                                                                autocomplete="off" />
                                                        </td>
                                                        <td class="px-3 py-2.5 align-middle">
                                                            <select
                                                                class="block w-full min-w-[9.5rem] max-w-[11rem] min-h-10 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm leading-normal text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                                x-model="titikData(minggu, titik.id).salmonella">
                                                                <option value="" disabled>Pilih</option>
                                                                <option value="Negatif">Negatif</option>
                                                                <option value="Positif">Positif</option>
                                                            </select>
                                                        </td>
                                                        <td class="px-3 py-2.5 align-middle">
                                                            <select
                                                                class="block w-full min-w-[9.5rem] max-w-[12rem] min-h-10 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm leading-normal text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                                x-model="titikData(minggu, titik.id).status">
                                                                <option value="" disabled>Pilih</option>
                                                                <option value="Baik">Baik</option>
                                                                <option value="Tidak Baik">Tidak Baik</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                        <p class="mt-3 text-xs text-gray-500">
                                            Titik boleh dikosongkan. Jika mulai mengisi, lengkapi pH, ALT, Salmonella, dan Status untuk titik tersebut.
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </x-ui.card-content>
                    </x-ui.card>
                </template>

                <div class="flex flex-wrap gap-2" x-show="availableUnits().length > 0">
                    <select class="w-full sm:w-80 h-11 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        x-on:change="tambahUnit($event.target.value); $event.target.value='';">
                        <option value="" selected disabled>+ Tambah Unit IPAM</option>
                        <template x-for="u in availableUnits()" :key="u.id">
                            <option :value="u.id" x-text="u.nama_unit"></option>
                        </template>
                    </select>
                </div>

                <x-pemantauan.ipam.catatan-laporan />
                <x-pemantauan.ipam.rekapitulasi />

                <div class="flex flex-col sm:flex-row gap-3 justify-end pt-4">
                    <x-ui.button variant="outline" type="button" x-on:click="backToList()">Batal</x-ui.button>
                    <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white" type="button"
                        x-bind:disabled="saving || loadingEdit"
                        x-on:click="handleSubmit()">
                        <span x-show="!saving" x-text="editingReportId ? 'Perbarui Laporan' : 'Simpan Laporan'"></span>
                        <span x-show="saving">Menyimpan...</span>
                    </x-ui.button>
                </div>

                <x-pemantauan.ipam.modal-sukses />
            </div>
        </template>
    </div>
@endsection
