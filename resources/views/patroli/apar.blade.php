@extends('layouts.app')

@section('title', 'Pemantauan APAR - Patroli')
@section('page_title', 'Pemantauan APAR')

@section('content')
    <div class="patroli-apar-page mx-auto max-w-4xl space-y-4 pb-32"
        x-data="patroliAparPage({{ \Illuminate\Support\Js::from([
            'scanPayload' => $scanPayload,
            'initialApar' => $initialApar,
            'continueLokasiSections' => $continueLokasiSections ?? [],
            'showContinueLoading' => $showContinueLoading ?? false,
            'readOnly' => $readOnly ?? false,
            'scanError' => $scanError,
            'storeUrl' => $storeUrl,
            'resolveUrl' => $resolveUrl,
            'temuanHref' => $temuanHref ?? route('patroli.temuan', [], false),
        ]) }})">
        @include('patroli.partials.loading-continue')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800" x-show="readOnly" x-cloak>
            Mode lihat. Periode pemeriksaan APAR sudah selesai dan data tidak dapat diubah.
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700" x-show="scanError" x-text="scanError"></div>
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Form Pemeriksaan APAR</h1>
                <p class="mt-0.5 text-sm text-gray-500">Isi data pemeriksaan APAR hasil scan QR Code</p>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-gray-600 sm:whitespace-nowrap">
                <x-icon name="shield-check" class="h-3.5 w-3.5 text-blue-500" />
                <span><strong class="text-blue-700" x-text="totalAPAR()"></strong> APAR · <span x-text="totalLokasi()"></span> lokasi</span>
            </div>
        </div>

        <div class="space-y-4">
        <template x-for="lokasi in lokasiSections" :key="lokasi.id">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-white px-4 py-3">
                    <div class="flex cursor-pointer items-center justify-between gap-3" x-on:click="toggleLokasi(lokasi.id)">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                <x-icon name="map-pin" class="h-4 w-4 text-blue-600" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-gray-900" x-text="lokasi.nama"></h3>
                                <p class="mt-0.5 text-[11px] text-gray-400" x-text="lokasi.aparList.length + ' APAR terdaftar'"></p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-red-50 hover:text-red-500"
                                title="Hapus lokasi dari pemeriksaan ini"
                                x-show="!readOnly"
                                x-on:click.stop="removeLokasi(lokasi.id)">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                            </button>
                            <x-icon name="chevron-up" class="h-4 w-4 text-gray-400" x-show="lokasi.expanded" />
                            <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" x-show="!lokasi.expanded" />
                        </div>
                    </div>
                </div>

                <div class="space-y-3 border-t border-gray-100 bg-gray-50/40 p-4" x-show="lokasi.expanded" x-cloak>
                    <template x-for="apar in lokasi.aparList" :key="'apar-' + aparId(apar)">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                            <div class="border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-bold text-gray-900" x-text="apar.kodeApar"></h4>
                                            <span class="inline-flex animate-pulse items-center rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-semibold text-white"
                                                x-show="apar.expiredStatus === 'expired'">EXPIRED</span>
                                            <span class="inline-flex items-center rounded-full bg-yellow-500 px-2 py-0.5 text-[10px] font-semibold text-white"
                                                x-show="apar.expiredStatus === 'warning'"
                                                x-text="apar.daysLeft + ' hari lagi'"></span>
                                        </div>
                                        <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-600">
                                            <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0" />
                                            <span x-text="apar.lokasiApar"></span>
                                        </div>
                                    </div>
                                    <button type="button"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-red-50 hover:text-red-500"
                                        x-show="!readOnly"
                                        x-on:click="removeApar(lokasi.id, aparId(apar))"
                                        aria-label="Hapus APAR">
                                        <x-icon name="x" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-4 p-4">
                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-semibold text-gray-700">
                                        Jenis &amp; Kapasitas <span class="text-[10px] font-normal text-gray-400">(dapat diupdate)</span>
                                    </x-ui.label>
                                    <x-ui.input class="bg-white text-sm text-gray-900" placeholder="Contoh: Powder - 3 Kg"
                                        x-model="apar.jenisKapasitas" x-bind:disabled="readOnly" />
                                </div>

                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-semibold text-gray-700">
                                        Tanggal Pemeriksaan <span class="text-[10px] font-normal text-gray-400">(otomatis)</span>
                                    </x-ui.label>
                                    <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        <x-icon name="calendar" class="h-4 w-4 text-gray-500" />
                                        <span class="text-sm font-medium text-gray-700" x-text="apar.tanggalPemeriksaan"></span>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-semibold text-gray-700">
                                        Kondisi Tabung <span class="text-red-500">*</span>
                                    </x-ui.label>
                                    <x-ui.textarea rows="3"
                                        class="resize-none bg-white text-sm text-gray-900"
                                        placeholder="Deskripsikan kondisi fisik tabung APAR secara detail (karat, penyok, cat mengelupas, dll)..."
                                        x-model="apar.kondisiTabung" x-bind:disabled="readOnly"></x-ui.textarea>
                                    <p class="text-[11px] text-gray-500">Jelaskan kondisi fisik tabung, pin, pressure gauge, selang, nozzle, dll.</p>
                                </div>

                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-semibold text-gray-700">
                                        Tanggal Expired <span class="text-[10px] font-normal text-gray-400">(dapat diupdate)</span>
                                    </x-ui.label>
                                    <x-ui.input class="bg-white text-sm text-gray-900" placeholder="Contoh: 15 Mei 2025"
                                        x-model="apar.tanggalExpired" x-bind:disabled="readOnly" />
                                    <p class="text-[11px] text-gray-500">Update jika berbeda dari data sebelumnya</p>
                                </div>

                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-semibold text-gray-700">
                                        Kondisi Segel <span class="text-red-500">*</span>
                                    </x-ui.label>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <button type="button"
                                            class="flex min-h-[3rem] cursor-pointer items-center justify-center gap-2 rounded-lg border-2 px-4 py-3 transition-colors"
                                            x-bind:class="sealOptionClass(apar, 'tersegel')"
                                            x-bind:disabled="readOnly"
                                            x-on:click="setKondisiSegel(apar, 'tersegel')">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2"
                                                x-bind:class="sealDotClass(apar, 'tersegel')">
                                                <span class="block h-2 w-2 rounded-full bg-emerald-500 transition-opacity"
                                                    x-bind:class="apar.kondisiSegel === 'tersegel' ? 'opacity-100' : 'opacity-0'"></span>
                                            </span>
                                            <span class="text-sm leading-none">Tersegel</span>
                                        </button>
                                        <button type="button"
                                            class="flex min-h-[3rem] cursor-pointer items-center justify-center gap-2 rounded-lg border-2 px-4 py-3 transition-colors"
                                            x-bind:class="sealOptionClass(apar, 'tidak-tersegel')"
                                            x-bind:disabled="readOnly"
                                            x-on:click="setKondisiSegel(apar, 'tidak-tersegel')">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2"
                                                x-bind:class="sealDotClass(apar, 'tidak-tersegel')">
                                                <span class="block h-2 w-2 rounded-full bg-red-500 transition-opacity"
                                                    x-bind:class="apar.kondisiSegel === 'tidak-tersegel' ? 'opacity-100' : 'opacity-0'"></span>
                                            </span>
                                            <span class="text-sm leading-none">Terbuka</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-semibold text-gray-700">
                                        Foto Kondisi APAR
                                        <span class="text-[10px] font-normal text-gray-400">(opsional)</span>
                                    </x-ui.label>
                                    <div class="flex flex-wrap gap-2" x-show="!readOnly">
                                        <x-ui.button variant="outline" size="sm" type="button"
                                            class="gap-1.5 text-xs text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                            x-on:click="pickAparCamera(apar)">
                                            <x-icon name="camera" class="h-3.5 w-3.5" />
                                            Ambil Foto
                                        </x-ui.button>
                                        <x-ui.button variant="outline" size="sm" type="button"
                                            class="gap-1.5 text-xs text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                            x-on:click="pickAparGallery(apar)">
                                            <x-icon name="image" class="h-3.5 w-3.5" />
                                            Galeri
                                        </x-ui.button>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <template x-for="photo in (apar.fotoKondisi ?? [])" :key="photo.id">
                                            <div class="group relative h-16 w-16 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                                                <img x-bind:src="photo.preview" alt="" loading="lazy"
                                                    class="h-full w-full object-cover" />
                                                <button type="button"
                                                    class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-white opacity-90 hover:bg-red-700"
                                                    x-on:click="removeAparPhoto(apar, photo.id)"
                                                    x-show="!photo.existing"
                                                    aria-label="Hapus foto">
                                                    <x-icon name="x" class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </template>
                                        <div class="flex h-16 w-16 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50"
                                            x-show="!(apar.fotoKondisi ?? []).length">
                                            <x-icon name="image" class="h-5 w-5 text-gray-400" />
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-gray-500" x-show="!readOnly">Opsional, maks. 5 foto. Dikompresi otomatis saat disimpan.</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
        </div>

        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-gray-500"
            x-show="lokasiSections.length === 0">
            Belum ada APAR dalam pemeriksaan ini.
        </div>

        <a href="{{ route('patroli.scan', ['type' => 'apar', 'continue' => 1]) }}"
            x-show="!readOnly"
            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 py-3.5 text-sm font-medium text-gray-500 transition-all duration-150 hover:border-blue-400 hover:bg-blue-50/50 hover:text-blue-600">
            <x-icon name="qr-code" class="h-4 w-4" />
            Scan QR APAR Berikutnya
        </a>

        <template x-teleport="body">
            <div data-patroli-apar-footer
                class="fixed bottom-0 left-0 right-0 z-[999] flex flex-col items-center justify-between gap-3 border-t border-gray-200 bg-white px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] sm:flex-row">
                <div class="text-center text-sm text-gray-500 sm:text-left">
                    Menyimpan pemeriksaan
                    <strong class="text-gray-800" x-text="totalAPAR() + ' APAR'"></strong>
                    di
                    <strong class="text-gray-800" x-text="totalLokasi() + ' lokasi'"></strong>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('patroli.riwayat') }}"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50">
                        Batal
                    </a>
                    <x-ui.button size="sm" type="button"
                        class="gap-2 bg-blue-600 px-5 text-sm text-white hover:bg-blue-700"
                        x-on:click="save()"
                        x-show="!readOnly"
                        x-bind:disabled="saving || !canSave()">
                        <x-icon name="check-circle2" class="h-4 w-4" />
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Semua'"></span>
                    </x-ui.button>
                </div>
            </div>
        </template>

        @include('patroli.partials.apar.modal-success')
    </div>
@endsection
