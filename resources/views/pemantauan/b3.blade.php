@extends('layouts.app')

@section('title', 'Pemantauan Limbah B3 - Safety Patrol K3LH')
@section('page_title', 'Pemantauan Limbah B3')

@php
    use Illuminate\Support\Js;
@endphp

@section('content')
    <div class="space-y-4" x-data="pemantauanB3({{ Js::from($b3PageConfig) }})">
        <template x-if="view === 'list'">
            <div class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Pemantauan Limbah B3</h1>
                        <p class="mt-0.5 text-sm text-gray-500">Daftar laporan semester limbah B3, logbook, dan manifest</p>
                    </div>
                    <x-ui.button class="w-full gap-2 bg-blue-600 text-white hover:bg-blue-700 sm:w-auto" type="button"
                        x-show="canManage" x-on:click="openForm()">
                        <x-icon name="plus" class="h-4 w-4" />
                        Buat Laporan Baru
                    </x-ui.button>
                </div>

                <x-ui.card class="border-0 shadow-sm">
                    <x-ui.card-content class="p-4">
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <div class="relative min-w-0 flex-1">
                                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <x-ui.input type="search" placeholder="Cari periode, status, atau progress..."
                                    class="h-10 w-full bg-white pl-9 text-gray-900" x-model="q" />
                            </div>
                            <select
                                class="h-10 w-full shrink-0 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-44"
                                x-model="filterStatus">
                                <option value="semua">Semua Status</option>
                                <option value="berlangsung">Berlangsung</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card class="border-0 shadow-sm">
                    <x-ui.card-content class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[720px] text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Nama Laporan</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Progress</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(r, i) in paginated()" :key="r.id">
                                        <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                            <td class="px-4 py-3 text-gray-500" x-text="paginationMeta().from + i"></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-700" x-text="r.tanggal"></td>
                                            <td class="max-w-xs px-4 py-3 font-medium text-gray-700" x-text="r.nama_laporan"></td>
                                            <td class="max-w-[200px] px-4 py-3 text-gray-600" x-text="r.jumlah"></td>
                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                    :class="statusClass(r.status)" x-text="r.status"></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-1">
                                                    <button type="button"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                                        title="Lihat laporan" aria-label="Lihat laporan"
                                                        x-show="!canManage" x-cloak
                                                        x-on:click="openViewReport(r)">
                                                        <x-icon name="eye" class="h-4 w-4" />
                                                    </button>
                                                    <template x-if="canManage">
                                                        <div class="flex items-center gap-1">
                                                            <button type="button"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                                                title="Edit laporan" aria-label="Edit laporan"
                                                                x-on:click="openEditReport(r)">
                                                                <x-icon name="pencil" class="h-4 w-4" />
                                                            </button>
                                                            <button type="button"
                                                                class="inline-flex h-8 items-center gap-1 rounded-md px-2 text-xs font-medium text-emerald-600 transition-colors hover:bg-emerald-50"
                                                                title="Tandai selesai" x-show="r.status === 'Berlangsung'" x-cloak
                                                                x-on:click="tandaiSelesai(r.id)">
                                                                <x-icon name="check-circle2" class="h-3.5 w-3.5" />
                                                                <span class="hidden sm:inline">Selesai</span>
                                                            </button>
                                                            <button type="button"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                                title="Hapus laporan" aria-label="Hapus laporan"
                                                                x-on:click="hapusLaporan(r)">
                                                                <x-icon name="trash" class="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filtered().length === 0">
                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                            <span x-show="reports.length === 0">Belum ada laporan B3.<span x-show="canManage"> Mulai dengan tombol Buat Laporan Baru.</span></span>
                                            <span x-show="reports.length > 0">Tidak ada data yang cocok.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <x-ui.list-pagination />
                    </x-ui.card-content>
                </x-ui.card>
            </div>
        </template>

        <template x-if="view === 'form'">
            <div class="mx-auto max-w-6xl space-y-5 pb-8">
                <div class="flex items-center gap-3">
                    <x-ui.button variant="ghost" size="icon" type="button" x-on:click="backToList()" aria-label="Kembali">
                        <x-icon name="arrow-left" class="h-5 w-5" />
                    </x-ui.button>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900"
                            x-text="isReadOnly ? 'Detail Laporan Limbah B3' : (editingReportId ? 'Ubah Laporan Limbah B3' : 'Buat Laporan Limbah B3')"></h1>
                        <p class="text-sm text-gray-500"
                            x-text="isReadOnly ? 'Mode lihat — data laporan hanya dapat dilihat' : 'Input data semester, jenis limbah, logbook, dan manifest'"></p>
                    </div>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800" x-show="isReadOnly" x-cloak>
                    Mode lihat. Laporan limbah B3 hanya dapat dilihat dan tidak dapat diubah.
                </div>

                <x-ui.card class="border border-gray-200 shadow-sm">
                    <x-ui.card-content class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <x-ui.label>Semester <span class="text-red-500" x-show="!isReadOnly">*</span></x-ui.label>
                            <select x-model="selectedSemester" x-bind:disabled="isReadOnly"
                                class="h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-70">
                                <option value="1">Semester I</option>
                                <option value="2">Semester II</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <x-ui.label>Tahun <span class="text-red-500" x-show="!isReadOnly">*</span></x-ui.label>
                            <div x-show="!isReadOnly">
                                <x-ui.year-picker x-model="selectedTahun" size="sm" />
                            </div>
                            <x-ui.input x-show="isReadOnly" x-cloak type="text" x-model="selectedTahun" disabled class="h-10 bg-gray-50" />
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                <div class="grid grid-cols-3 gap-3">
                    <x-ui.card class="border border-gray-200 shadow-sm">
                        <x-ui.card-content class="p-4 text-center">
                            <p class="text-2xl font-bold text-blue-600" x-text="totalJenis()"></p>
                            <p class="mt-1 text-xs text-gray-500">Jenis Limbah</p>
                        </x-ui.card-content>
                    </x-ui.card>
                    <x-ui.card class="border border-gray-200 shadow-sm">
                        <x-ui.card-content class="p-4 text-center">
                            <p class="text-2xl font-bold text-emerald-600" x-text="totalLogbook()"></p>
                            <p class="mt-1 text-xs text-gray-500">Logbook</p>
                        </x-ui.card-content>
                    </x-ui.card>
                    <x-ui.card class="border border-gray-200 shadow-sm">
                        <x-ui.card-content class="p-4 text-center">
                            <p class="text-2xl font-bold text-orange-600" x-text="totalManifest()"></p>
                            <p class="mt-1 text-xs text-gray-500">Manifest</p>
                        </x-ui.card-content>
                    </x-ui.card>
                </div>

                <x-ui.card class="overflow-hidden border border-gray-200 shadow-sm">
                    <x-ui.card-header class="cursor-pointer border-b border-gray-100 bg-white px-4 py-3" x-on:click="toggle('jenis')">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <x-ui.card-title class="text-base">Jenis Limbah B3</x-ui.card-title>
                                <p class="mt-0.5 text-xs text-gray-500">Master jenis limbah (nama, kode, sumber, karakteristik). Klik Tambah Jenis Limbah, tiap baris wajib lengkap.</p>
                            </div>
                            <x-icon name="chevron-up" class="h-4 w-4 text-gray-400" x-show="expanded.jenis" />
                            <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" x-show="!expanded.jenis" />
                        </div>
                    </x-ui.card-header>
                    <x-ui.card-content class="space-y-3 bg-gray-50/40 p-4" x-show="expanded.jenis">
                        <p x-show="jenisList.length === 0" class="py-6 text-center text-sm text-gray-500">
                            <span x-show="!isReadOnly">Belum ada jenis limbah. Klik <span class="font-medium">Tambah Jenis Limbah</span> untuk mulai mengisi.</span>
                            <span x-show="isReadOnly" x-cloak>Belum ada jenis limbah pada laporan ini.</span>
                        </p>
                        <template x-for="(item, index) in jenisList" :key="item.id">
                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <p class="text-sm font-semibold text-gray-900" x-text="'Jenis Limbah #' + (index + 1)"></p>
                                    <button type="button" class="text-red-400 hover:text-red-600" x-show="!isReadOnly" x-on:click="removeJenis(item.id)">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <x-ui.input placeholder="Nama limbah" x-model="item.nama_limbah" x-bind:disabled="isReadOnly" />
                                    <x-ui.input placeholder="Kode limbah (A337-1)" x-model="item.kode_limbah" x-bind:disabled="isReadOnly" />
                                    <x-ui.input placeholder="Sumber limbah" x-model="item.sumber_limbah" x-bind:disabled="isReadOnly" />
                                    <x-ui.input placeholder="Karakteristik" x-model="item.karakteristik" x-bind:disabled="isReadOnly" />
                                    <x-ui.input placeholder="Pengemasan" x-model="item.pengemasan" x-bind:disabled="isReadOnly" />
                                    <x-ui.input type="number" placeholder="Masa simpan (hari)" x-model="item.masa_simpan_hari" x-bind:disabled="isReadOnly" />
                                </div>
                            </div>
                        </template>
                        <x-ui.button type="button" variant="outline" class="w-full border-dashed text-blue-600 hover:bg-blue-50" x-show="!isReadOnly" x-on:click="addJenis()">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah Jenis Limbah
                        </x-ui.button>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card class="overflow-hidden border border-gray-200 shadow-sm">
                    <x-ui.card-header class="cursor-pointer border-b border-gray-100 bg-white px-4 py-3" x-on:click="toggle('logbook')">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <x-ui.card-title class="text-base">Logbook Limbah B3</x-ui.card-title>
                                <p class="mt-0.5 text-xs text-gray-500">Catatan masuk dan keluar limbah per bulan</p>
                            </div>
                            <x-icon name="chevron-up" class="h-4 w-4 text-gray-400" x-show="expanded.logbook" />
                            <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" x-show="!expanded.logbook" />
                        </div>
                    </x-ui.card-header>
                    <x-ui.card-content class="space-y-3 bg-gray-50/40 p-4" x-show="expanded.logbook">
                        <template x-for="bulan in logbookBulanList" :key="bulan.id">
                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <button type="button" class="flex w-full items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-3 text-left hover:bg-gray-100"
                                    x-on:click="toggleLogbookBulan(bulan.id)">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900" x-text="bulan.nama"></p>
                                        <p class="text-xs text-gray-500">Otomatis dari <span x-text="semesterLabel()"></span></p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700"
                                            x-text="bulan.entries.length + ' catatan'"></span>
                                        <x-icon name="chevron-up" class="h-4 w-4 text-gray-400" x-show="bulan.expanded" />
                                        <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" x-show="!bulan.expanded" />
                                    </div>
                                </button>

                                <div class="space-y-3 p-4" x-show="bulan.expanded">
                                    <p x-show="bulan.entries.length === 0" class="py-6 text-center text-sm text-gray-500">
                                        <span x-show="!isReadOnly">Belum ada catatan. Klik <span class="font-medium">Tambah Catatan</span> untuk mulai mengisi.</span>
                                        <span x-show="isReadOnly" x-cloak>Belum ada catatan pada bulan ini.</span>
                                    </p>
                                    <template x-for="(item, index) in bulan.entries" :key="item.id">
                                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                                            <div class="mb-3 flex items-center justify-between">
                                                <p class="text-sm font-semibold text-gray-800" x-text="'Catatan ' + bulan.nama + ' #' + (index + 1)"></p>
                                                <button type="button" class="text-red-400 hover:text-red-600" x-show="!isReadOnly" x-on:click="removeLogbook(bulan.id, item.id)">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                                <div class="space-y-1.5">
                                                    <x-ui.label class="text-xs text-gray-600">Tanggal Masuk Limbah <span class="text-red-500" x-show="!isReadOnly">*</span></x-ui.label>
                                                    <x-ui.input type="date" x-model="item.tanggal_masuk" x-bind:disabled="isReadOnly" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label class="text-xs text-gray-600">Tanggal Keluar Limbah <span class="text-gray-400" x-show="!isReadOnly">(opsional)</span></x-ui.label>
                                                    <x-ui.input type="date" x-model="item.tanggal_keluar" x-bind:disabled="isReadOnly" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label class="text-xs text-gray-600">Jenis Limbah <span class="text-red-500" x-show="!isReadOnly">*</span></x-ui.label>
                                                    <x-ui.input placeholder="Contoh: Oli Bekas" x-model="item.jenis_limbah" x-bind:disabled="isReadOnly" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label class="text-xs text-gray-600">Sumber Limbah <span class="text-red-500" x-show="!isReadOnly">*</span></x-ui.label>
                                                    <x-ui.input placeholder="Contoh: Workshop Mesin" x-model="item.sumber_limbah" x-bind:disabled="isReadOnly" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label class="text-xs text-gray-600">Jumlah Masuk (kg) <span class="text-red-500" x-show="!isReadOnly">*</span></x-ui.label>
                                                    <x-ui.input type="number" step="0.01" placeholder="0.00" x-model="item.jumlah_masuk_kg" x-bind:disabled="isReadOnly" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label class="text-xs text-gray-600">Jumlah Keluar (kg) <span class="text-gray-400" x-show="!isReadOnly">(jika ada)</span></x-ui.label>
                                                    <x-ui.input type="number" step="0.01" placeholder="0.00" x-model="item.jumlah_keluar_kg" x-bind:disabled="isReadOnly" />
                                                </div>
                                                <div class="space-y-1.5 md:col-span-2">
                                                    <x-ui.label class="text-xs text-gray-600">Pengemasan</x-ui.label>
                                                    <x-ui.input placeholder="Contoh: Drum/Tong, Jerigen" x-model="item.pengemasan" x-bind:disabled="isReadOnly" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <x-ui.button type="button" variant="outline" size="sm" class="w-full border-dashed text-blue-600 hover:bg-blue-50" x-show="!isReadOnly" x-on:click="addLogbook(bulan.id)">
                                        <x-icon name="plus" class="h-4 w-4" />
                                        Tambah Catatan <span x-text="bulan.nama"></span>
                                    </x-ui.button>
                                </div>
                            </div>
                        </template>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card class="overflow-hidden border border-gray-200 shadow-sm">
                    <x-ui.card-header class="cursor-pointer border-b border-gray-100 bg-white px-4 py-3" x-on:click="toggle('manifest')">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <x-ui.card-title class="text-base">Manifest Limbah B3</x-ui.card-title>
                                <p class="mt-0.5 text-xs text-gray-500">Dokumen manifest pengangkutan dan penerimaan limbah. Klik Tambah Manifest bila sudah ada data.</p>
                            </div>
                            <x-icon name="chevron-up" class="h-4 w-4 text-gray-400" x-show="expanded.manifest" />
                            <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" x-show="!expanded.manifest" />
                        </div>
                    </x-ui.card-header>
                    <x-ui.card-content class="space-y-3 bg-gray-50/40 p-4" x-show="expanded.manifest">
                        <p x-show="manifestList.length === 0" class="py-6 text-center text-sm text-gray-500">
                            <span x-show="!isReadOnly">Belum ada manifest. Klik <span class="font-medium">Tambah Manifest</span> jika sudah ada data pengangkutan.</span>
                            <span x-show="isReadOnly" x-cloak>Belum ada manifest pada laporan ini.</span>
                        </p>
                        <template x-for="(item, index) in manifestList" :key="item.id">
                            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-gray-900" x-text="'Manifest #' + (index + 1)"></p>
                                    <button type="button" class="text-red-400 hover:text-red-600" x-show="!isReadOnly" x-on:click="removeManifest(item.id)">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>

                                <div>
                                    <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Informasi Manifest</h4>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                        <x-ui.input placeholder="Nomor manifest" x-model="item.nomor_manifest" x-bind:disabled="isReadOnly" />
                                        <div class="space-y-1.5">
                                            <x-ui.label class="text-xs text-gray-600">Tanggal Manifest</x-ui.label>
                                            <x-ui.input type="date" x-model="item.tanggal_manifest" x-bind:disabled="isReadOnly" />
                                        </div>
                                        <x-ui.input placeholder="Tujuan pengangkutan" x-model="item.tujuan_pengangkutan" x-bind:disabled="isReadOnly" />
                                    </div>
                                </div>

                                <div>
                                    <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Pengirim</h4>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <x-ui.input placeholder="Nama pengirim" x-model="item.nama_pengirim" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Fasilitas penyimpanan" x-model="item.nama_fasilitas_penyimpanan" x-bind:disabled="isReadOnly" />
                                        <x-ui.textarea rows="2" placeholder="Alamat pengirim" x-model="item.alamat_pengirim" x-bind:disabled="isReadOnly"></x-ui.textarea>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <x-ui.input placeholder="PJ pengirim" x-model="item.penanggung_jawab_pengirim" x-bind:disabled="isReadOnly" />
                                            <x-ui.input placeholder="Jabatan PJ" x-model="item.jabatan_pj_pengirim" x-bind:disabled="isReadOnly" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Data Limbah</h4>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                        <x-ui.input placeholder="Kode limbah" x-model="item.kode_limbah" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Nama limbah" x-model="item.nama_limbah" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Nama teknik" x-model="item.nama_teknik" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Karakteristik" x-model="item.karakteristik_limbah" x-bind:disabled="isReadOnly" />
                                        <div class="space-y-1.5">
                                            <x-ui.label class="text-xs text-gray-600">Periode Limbah Mulai</x-ui.label>
                                            <x-ui.input type="date" x-model="item.periode_limbah_mulai" x-bind:disabled="isReadOnly" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <x-ui.label class="text-xs text-gray-600">Periode Limbah Selesai</x-ui.label>
                                            <x-ui.input type="date" x-model="item.periode_limbah_selesai" x-bind:disabled="isReadOnly" />
                                        </div>
                                        <x-ui.input placeholder="Jenis kemasan" x-model="item.jenis_kemasan" x-bind:disabled="isReadOnly" />
                                        <x-ui.input type="number" placeholder="Jumlah kemasan" x-model="item.jumlah_kemasan" x-bind:disabled="isReadOnly" />
                                        <x-ui.input type="number" step="0.001" placeholder="Jumlah (ton)" x-model="item.jumlah_limbah_ton" x-bind:disabled="isReadOnly" />
                                        <x-ui.input class="md:col-span-3" placeholder="Keterangan tambahan" x-model="item.keterangan_tambahan" x-bind:disabled="isReadOnly" />
                                    </div>
                                </div>

                                <div>
                                    <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Pengangkut</h4>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                        <x-ui.input placeholder="Nama pengangkut" x-model="item.nama_pengangkut" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Telepon darurat" x-model="item.no_telepon_darurat" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Nomor kendaraan" x-model="item.identitas_alat_angkut" x-bind:disabled="isReadOnly" />
                                        <x-ui.textarea rows="2" placeholder="Alamat pengangkut" x-model="item.alamat_pengangkut" x-bind:disabled="isReadOnly"></x-ui.textarea>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <div class="space-y-1.5">
                                                <x-ui.label class="text-xs text-gray-600">Waktu Mulai Pengangkutan</x-ui.label>
                                                <x-ui.input type="datetime-local" x-model="item.waktu_mulai_pengangkutan" x-bind:disabled="isReadOnly" />
                                            </div>
                                            <div class="space-y-1.5">
                                                <x-ui.label class="text-xs text-gray-600">Waktu Selesai Pengangkutan</x-ui.label>
                                                <x-ui.input type="datetime-local" x-model="item.waktu_selesai_pengangkutan" x-bind:disabled="isReadOnly" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <x-ui.input type="number" placeholder="Jumlah ritase" x-model="item.jumlah_ril" x-bind:disabled="isReadOnly" />
                                            <x-ui.input placeholder="PJ pengangkut" x-model="item.penanggung_jawab_pengangkut" x-bind:disabled="isReadOnly" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Penerima</h4>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                        <x-ui.input placeholder="Nama penerima" x-model="item.nama_penerima" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Telepon penerima" x-model="item.no_telepon_penerima" x-bind:disabled="isReadOnly" />
                                        <x-ui.input placeholder="Jenis pengelolaan" x-model="item.jenis_pengelolaan" x-bind:disabled="isReadOnly" />
                                        <x-ui.textarea rows="2" placeholder="Alamat penerima" x-model="item.alamat_penerima" x-bind:disabled="isReadOnly"></x-ui.textarea>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <x-ui.input type="number" step="0.01" placeholder="Diterima (kg)" x-model="item.jumlah_diterima_kg" x-bind:disabled="isReadOnly" />
                                            <x-ui.input placeholder="PJ penerima" x-model="item.penanggung_jawab_penerima" x-bind:disabled="isReadOnly" />
                                        </div>
                                        <x-ui.input placeholder="Jabatan PJ penerima" x-model="item.jabatan_pj_penerima" x-bind:disabled="isReadOnly" />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <x-ui.button type="button" variant="outline" class="w-full border-dashed text-blue-600 hover:bg-blue-50" x-show="!isReadOnly" x-on:click="addManifest()">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah Manifest
                        </x-ui.button>
                    </x-ui.card-content>
                </x-ui.card>

                <div class="flex justify-end gap-3 pt-2">
                    <x-ui.button type="button" variant="outline" class="text-gray-700" x-on:click="backToList()"
                        x-text="isReadOnly ? 'Kembali' : 'Batal'"></x-ui.button>
                    <x-ui.button type="button" class="bg-blue-600 text-white hover:bg-blue-700" x-show="!isReadOnly"
                        x-on:click="handleSubmit()" x-bind:disabled="saving">
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Laporan B3'"></span>
                    </x-ui.button>
                </div>

                <template x-if="showSuccess">
                    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <x-ui.card class="w-full max-w-sm border-0 shadow-2xl">
                            <x-ui.card-content class="space-y-4 p-6 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                                    <x-icon name="check-circle2" class="h-8 w-8 text-emerald-600" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Laporan B3 Berhasil Disimpan</h2>
                                    <p class="mt-1 text-sm text-gray-500">
                                        <span x-text="semesterLabel()"></span> <span x-text="selectedTahun"></span>
                                    </p>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-4 text-left text-sm">
                                    <div class="flex justify-between"><span class="text-gray-500">Jenis Limbah</span><strong x-text="totalJenis()"></strong></div>
                                    <div class="mt-2 flex justify-between"><span class="text-gray-500">Logbook</span><strong x-text="totalLogbook()"></strong></div>
                                    <div class="mt-2 flex justify-between"><span class="text-gray-500">Manifest</span><strong x-text="totalManifest()"></strong></div>
                                </div>
                                <x-ui.button type="button" class="w-full bg-blue-600 text-white hover:bg-blue-700" x-on:click="closeSuccessAndList()">
                                    Kembali ke Daftar
                                </x-ui.button>
                            </x-ui.card-content>
                        </x-ui.card>
                    </div>
                </template>
            </div>
        </template>
    </div>
@endsection
