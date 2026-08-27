@props([
    'triwulanKeys' => [],
])

<div x-show="view === 'form'" x-cloak class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <x-ui.button variant="ghost" size="icon" type="button" aria-label="Kembali ke daftar" x-on:click="backToList()">
            <x-icon name="arrow-left" class="w-5 h-5" />
        </x-ui.button>
        <div>
            <div x-show="!editingReportId">
                <h1 class="text-xl font-bold text-gray-900">Buat Laporan Pemantauan IPAL</h1>
                <p class="text-sm text-gray-500">Pilih triwulan, kemudian isi catatan harian per bulan</p>
            </div>
            <div x-show="editingReportId" x-cloak>
                <h1 class="text-xl font-bold text-gray-900">Edit laporan pemantauan IPAL</h1>
                <p class="text-sm text-gray-500">Periode dapat diubah kapan saja, termasuk laporan yang statusnya sudah selesai.</p>
            </div>
        </div>
    </div>

    <x-ui.card class="border border-gray-200 shadow-sm">
        <x-ui.card-header class="pb-3">
            <x-ui.card-title class="text-base">Periode Triwulan</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-0">
            <div class="space-y-2">
                <x-ui.label>Triwulan <span class="text-red-500">*</span></x-ui.label>
                <select x-model="selectedTriwulan"
                    class="flex h-11 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <option value="">Pilih triwulan</option>
                    @foreach ($triwulanKeys as $tw)
                        <option value="{{ $tw }}">{{ $tw }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2">
                <x-ui.label>Tahun <span class="text-red-500">*</span></x-ui.label>
                <x-ui.year-picker x-model="selectedTahun" />
            </div>
        </x-ui.card-content>
    </x-ui.card>

    <template x-for="(bulan, idxBulan) in bulanList" :key="bulan.nama + '-' + idxBulan">
        <x-ui.card class="overflow-hidden border border-gray-200 shadow-sm">
            <x-ui.card-header class="border-b border-gray-100 bg-white px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex min-w-0 flex-1 cursor-pointer items-center gap-3" x-on:click="toggleBulanExpand(idxBulan)">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                            <x-icon name="calendar" class="h-4 w-4 text-blue-600" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <x-ui.card-title class="truncate text-sm font-semibold text-gray-900">
                                <span x-text="bulan.nama"></span>
                                <span class="font-normal text-gray-500" x-text="' ' + selectedTahun"></span>
                            </x-ui.card-title>
                            <p class="mt-0.5 text-[11px] text-gray-500">
                                <span x-text="bulan.catatan.length + ' catatan'"></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <x-ui.button variant="outline" size="sm" class="gap-1 shrink-0" type="button"
                            x-on:click.stop="addCatatan(idxBulan)">
                            <x-icon name="plus" class="w-4 h-4" />
                            <span class="hidden sm:inline">Tambah Catatan</span>
                        </x-ui.button>
                        <button type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100"
                            x-on:click="toggleBulanExpand(idxBulan)" aria-label="Buka tutup bulan">
                            <x-icon name="chevron-up" class="h-4 w-4" x-show="bulan.expanded" />
                            <x-icon name="chevron-down" class="h-4 w-4" x-show="!bulan.expanded" />
                        </button>
                    </div>
                </div>
            </x-ui.card-header>
            <x-ui.card-content class="space-y-4 bg-gray-50/40 p-4" x-show="bulan.expanded">
                <p x-show="bulan.catatan.length === 0" class="text-sm text-gray-500 text-center py-6">
                    Belum ada catatan. Klik <span class="font-medium">Tambah Catatan</span> untuk mulai mengisi.
                </p>
                <template x-for="(catatan, idxCatatan) in bulan.catatan" :key="String(catatan.id)">
                    <div class="relative border border-gray-200 rounded-lg p-4 bg-white">
                        <x-ui.button variant="ghost" size="icon"
                            class="absolute right-2 top-2 h-8 w-8 text-red-400 hover:text-red-600 hover:bg-red-50"
                            type="button"
                            x-on:click="removeCatatan(idxBulan, catatan.id)">
                            <x-icon name="trash" class="w-4 h-4" />
                        </x-ui.button>
                        <p class="text-sm font-medium text-gray-700 mb-3">
                            Catatan #<span x-text="idxCatatan + 1"></span>
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <div class="space-y-1">
                                <x-ui.label class="text-xs">Tanggal Sampling *</x-ui.label>
                                <x-ui.input type="date" class="h-10 text-sm bg-white" x-model="catatan.tanggal" />
                            </div>
                            <div class="space-y-1">
                                <x-ui.label class="text-xs">Debit Input (m³/hari) *</x-ui.label>
                                <x-ui.input type="number" step="0.01" class="h-10 text-sm bg-white" placeholder="0.00"
                                    x-model="catatan.debitIn" />
                            </div>
                            <div class="space-y-1">
                                <x-ui.label class="text-xs">Debit Output (m³/hari) *</x-ui.label>
                                <x-ui.input type="number" step="0.01" class="h-10 text-sm bg-white" placeholder="0.00"
                                    x-model="catatan.debitOut" />
                            </div>
                            <div class="space-y-1">
                                <x-ui.label class="text-xs">pH *</x-ui.label>
                                <x-ui.input type="number" min="0" max="14" step="0.01" class="h-10 text-sm bg-white" placeholder="7.5"
                                    x-model="catatan.pH" />
                            </div>
                            <div class="space-y-1">
                                <x-ui.label class="text-xs">Suhu (°C) *</x-ui.label>
                                <x-ui.input type="number" min="-10" max="100" step="0.1" class="h-10 text-sm bg-white" placeholder="28.5"
                                    x-model="catatan.suhu" />
                            </div>
                        </div>
                    </div>
                </template>
            </x-ui.card-content>
        </x-ui.card>
    </template>

    <x-ui.card class="border border-gray-200 shadow-sm">
        <x-ui.card-header class="pb-3">
            <x-ui.card-title class="text-base">Evaluasi Kinerja IPAL Triwulan</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content class="space-y-5 pt-0">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <x-ui.label>Jenis Dampak</x-ui.label>
                    <x-ui.input class="h-11 bg-white" placeholder="Contoh: Penurunan kualitas air permukaan"
                        x-model="evaluasi.jenisDampak" />
                </div>
                <div class="space-y-2">
                    <x-ui.label>Sumber Dampak</x-ui.label>
                    <x-ui.input class="h-11 bg-white" placeholder="Contoh: Air limbah hasil operasional produksi"
                        x-model="evaluasi.sumberDampak" />
                </div>
            </div>
            <div class="space-y-2">
                <x-ui.label>Parameter Pemantauan</x-ui.label>
                <x-ui.input class="h-11 bg-white"
                    placeholder="Contoh: pH, BOD, COD, TSS, TDS, NH3, Minyak & Lemak, Coliform"
                    x-model="evaluasi.parameterPemantauan" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <x-ui.label>Tolak Ukur (Baku Mutu)</x-ui.label>
                    <x-ui.input class="h-11 bg-white" placeholder="PermenLH No... tahun..." x-model="evaluasi.tolakUkur" />
                </div>
                <div class="space-y-2">
                    <x-ui.label>Lokasi Pengelolaan</x-ui.label>
                    <x-ui.input class="h-11 bg-white" placeholder="Nama perusahaan + koordinat GPS"
                        x-model="evaluasi.lokasiPengelolaan" />
                </div>
            </div>
            <div class="space-y-2">
                <x-ui.label>Evaluasi / Hasil</x-ui.label>
                <x-ui.textarea rows="3"
                    placeholder="Analisis perbandingan hasil lab dengan baku mutu selama periode ini..."
                    x-model="evaluasi.evaluasiHasil"></x-ui.textarea>
            </div>
            <div class="space-y-2">
                <x-ui.label>Tindakan Perbaikan</x-ui.label>
                <x-ui.textarea rows="3"
                    placeholder="Langkah teknis yang diambil jika ditemukan parameter melebihi baku mutu..."
                    x-model="evaluasi.tindakanPerbaikan"></x-ui.textarea>
            </div>
        </x-ui.card-content>
    </x-ui.card>

    <div class="flex flex-col sm:flex-row gap-3 justify-end pt-4">
        <x-ui.button variant="outline" type="button" x-on:click="backToList()">Batal</x-ui.button>
        <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white" type="button"
            x-bind:disabled="saving" x-on:click="handleSubmit()">
            <span x-show="!saving">Simpan Laporan Triwulan</span>
            <span x-show="saving" x-cloak>Menyimpan...</span>
        </x-ui.button>
    </div>
</div>
