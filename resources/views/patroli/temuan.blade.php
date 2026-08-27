@extends('layouts.app')

@section('title', 'Temuan Bahaya - Patroli')
@section('page_title', 'Checklist Inspeksi')

@section('content')
    <div class="mx-auto max-w-4xl space-y-4 pb-32"
        x-data="patroliTemuanPage({{ \Illuminate\Support\Js::from([
            'scanPayload' => $scanPayload,
            'initialSection' => $initialSection,
            'continueSections' => $continueSections ?? [],
            'showContinueLoading' => $showContinueLoading ?? false,
            'readOnly' => $readOnly ?? false,
            'scanError' => $scanError,
            'storeUrl' => $storeUrl,
            'resolveUrl' => $resolveUrl,
            'aparHref' => $aparHref ?? route('patroli.apar', [], false),
        ]) }})">
        @include('patroli.partials.loading-continue')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800" x-show="readOnly" x-cloak>
            Mode lihat. Periode patroli temuan sudah selesai dan data inspeksi tidak dapat diubah.
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700" x-show="scanError" x-text="scanError"></div>
        <div class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700" x-show="saveError" x-text="saveError"></div>
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Checklist Inspeksi</h1>
                <p class="mt-0.5 text-sm text-gray-500">Periksa setiap item dan tandai kondisi yang ditemukan di lapangan</p>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-gray-600 sm:whitespace-nowrap">
                <x-icon name="map-pin" class="h-3.5 w-3.5 text-blue-500" />
                <span><span x-text="sections.length"></span> lokasi · <span x-text="totalItems()"></span> item · tambah lokasi di bawah</span>
            </div>
        </div>

        <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-gray-700">Progress Inspeksi</span>
                <span class="font-bold text-blue-600" x-text="progress() + '%'"></span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-blue-400 transition-all duration-500"
                    x-bind:style="`width: ${progress()}%`"></div>
            </div>
            <div class="flex flex-wrap gap-4 text-xs">
                <span class="text-gray-400">Belum: <strong class="text-gray-700" x-text="belum()"></strong></span>
                <span class="text-emerald-600">Sesuai: <strong x-text="doneYa()"></strong></span>
                <span class="text-red-500">Tidak Sesuai: <strong x-text="doneTidak()"></strong></span>
            </div>
        </div>

        <div class="flex items-start gap-2.5 rounded-lg border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-700">
            <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
            <span>
                Nilai Probability (P) dan Severity (S) telah ditetapkan oleh Kalab.
                Tingkat risiko dihitung <strong>otomatis</strong> oleh sistem. Petugas cukup menandai Ya atau Tidak,
                lalu isi analisa dan rekomendasi jika tidak sesuai.
            </span>
        </div>

        <template x-for="section in sections" :key="section.id">
            <x-ui.card class="overflow-hidden border border-gray-200 shadow-sm">
                <x-ui.card-header class="border-b border-gray-100 bg-white px-4 py-3">
                    <div class="flex cursor-pointer items-center gap-3" x-on:click="toggleExpand(section.id)">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                            <x-icon name="clipboard-list" class="h-4 w-4 text-blue-600" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <x-ui.card-title class="truncate text-sm font-semibold text-gray-900" x-text="section.nama"></x-ui.card-title>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                <span class="truncate text-[11px] text-gray-400" x-text="section.namaChecklist"></span>
                                <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-1.5 py-0 text-[10px] font-semibold text-blue-600"
                                    x-show="!sectionPersisted(section)">
                                    QR Auto-fill
                                </span>
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-1.5 py-0 text-[10px] font-semibold text-emerald-700"
                                    x-show="sectionPersisted(section)">
                                    Terinspeksi
                                </span>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700"
                                x-show="lokasiTidakSesuai(section) > 0"
                                x-text="lokasiTidakSesuai(section) + ' tidak sesuai'"></span>
                            <span class="text-xs font-medium text-gray-500">
                                <span x-text="lokasiDone(section)"></span>/<span x-text="section.items.length"></span>
                            </span>
                            <button type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-red-50 hover:text-red-500"
                                title="Hapus lokasi dari patroli ini"
                                x-show="sectionEditable(section) && !sectionPersisted(section)"
                                x-on:click.stop="removeLokasi(section.id)">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                            </button>
                            <x-icon name="chevron-up" class="h-4 w-4 text-gray-400" x-show="section.expanded" />
                            <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" x-show="!section.expanded" />
                        </div>
                    </div>

                    <div class="mt-2.5 h-1 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full transition-all duration-500"
                            x-bind:class="lokasiTidakSesuai(section) > 0 ? 'bg-gradient-to-r from-orange-400 to-red-400' : 'bg-gradient-to-r from-emerald-400 to-blue-400'"
                            x-bind:style="`width: ${lokasiProgress(section)}%`"></div>
                    </div>
                </x-ui.card-header>

                <x-ui.card-content class="space-y-3 bg-gray-50/40 p-4" x-show="section.expanded">
                    <template x-for="item in section.items" :key="item.id">
                        <div class="overflow-hidden rounded-xl border transition-all duration-200" x-bind:class="itemCardClass(item)">
                            <div class="p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-all"
                                        x-bind:class="indicatorClass(item)">
                                        <x-icon name="check-circle2" class="h-3 w-3 text-white" x-show="item.status === 'ya'" />
                                        <x-icon name="alert-triangle" class="h-2.5 w-2.5 text-white" x-show="item.status === 'tidak'" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold leading-snug text-gray-800" x-text="item.namaItem"></p>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            <div class="flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-400">
                                                <x-icon name="info" class="h-2.5 w-2.5" />
                                                <span>Bobot Kalab: P=<span x-text="item.probability"></span> x S=<span x-text="item.severity"></span> = <span x-text="score(item)"></span></span>
                                            </div>
                                            <span class="inline-flex h-4 items-center rounded-full border px-1.5 py-0 text-[10px] font-semibold"
                                                x-bind:class="riskClass(riskLevel(item))"
                                                x-text="riskLevel(item)"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="ml-8 mt-3 flex flex-wrap gap-2" x-show="sectionEditable(section)">
                                    <button type="button"
                                        class="flex items-center gap-1.5 rounded-lg border px-3.5 py-1.5 text-xs font-semibold transition-all duration-150"
                                        x-bind:class="yaButtonClass(item)"
                                        x-on:click="updateStatus(section.id, item.id, 'ya')">
                                        <x-icon name="shield-check" class="h-3.5 w-3.5" />
                                        Ya, Sesuai
                                    </button>
                                    <button type="button"
                                        class="flex items-center gap-1.5 rounded-lg border px-3.5 py-1.5 text-xs font-semibold transition-all duration-150"
                                        x-bind:class="tidakButtonClass(item)"
                                        x-on:click="updateStatus(section.id, item.id, 'tidak')">
                                        <x-icon name="shield-alert" class="h-3.5 w-3.5" />
                                        Tidak Sesuai
                                    </button>
                                </div>
                                <p class="ml-8 mt-2 text-xs font-semibold text-emerald-700" x-show="readOnly && item.status === 'ya'">
                                    Sesuai
                                </p>
                                <p class="ml-8 mt-2 text-xs font-semibold text-red-700" x-show="readOnly && item.status === 'tidak'">
                                    Tidak sesuai
                                </p>
                            </div>

                            <div class="space-y-4 border-t border-red-100 bg-white/80 p-4"
                                x-show="item.status === 'tidak' && sectionEditable(section)">
                                <div>
                                    <x-ui.label class="mb-1.5 block text-xs font-semibold text-red-700">
                                        Foto Dokumentasi <span class="text-red-500">*</span>
                                        <span class="ml-1 font-normal text-red-400">(wajib)</span>
                                    </x-ui.label>
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.button variant="outline" size="sm" type="button"
                                            class="h-8 gap-1.5 border-red-200 text-xs text-red-600 hover:bg-red-50"
                                            x-on:click="pickItemCamera(section.id, item)">
                                            <x-icon name="camera" class="h-3.5 w-3.5" />
                                            Ambil Foto
                                        </x-ui.button>
                                        <x-ui.button variant="outline" size="sm" type="button"
                                            class="h-8 gap-1.5 border-red-200 text-xs text-red-600 hover:bg-red-50"
                                            x-on:click="pickItemGallery(section.id, item)">
                                            <x-icon name="image" class="h-3.5 w-3.5" />
                                            Pilih dari Galeri
                                        </x-ui.button>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <template x-for="photo in (item.fotoDokumentasi ?? [])" :key="photo.id">
                                            <div class="group relative h-20 w-20 overflow-hidden rounded-lg border border-red-200 bg-red-50">
                                                <img x-bind:src="photo.preview" alt="Foto dokumentasi temuan"
                                                    class="h-full w-full object-cover" />
                                                <button type="button"
                                                    class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-white hover:bg-red-700"
                                                    x-on:click="removeItemPhoto(section.id, item.id, photo.id)"
                                                    aria-label="Hapus foto">
                                                    <x-icon name="x" class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </template>
                                        <div class="flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-red-200 bg-red-50"
                                            x-show="!(item.fotoDokumentasi ?? []).length">
                                            <x-icon name="image" class="h-5 w-5 text-red-300" />
                                            <span class="text-[9px] text-red-300">Belum ada foto</span>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-[10px] text-red-400">Foto wajib untuk temuan</p>
                                </div>

                                <div>
                                    <x-ui.label class="mb-1.5 block text-xs font-semibold text-gray-700">
                                        Analisa Risiko <span class="text-red-500">*</span>
                                    </x-ui.label>
                                    <x-ui.textarea rows="2"
                                        class="resize-none border-gray-200 bg-white text-sm text-gray-800 placeholder:text-gray-400"
                                        placeholder="Jelaskan potensi dampak dan penyebab kondisi tidak sesuai ini..."
                                        x-bind:value="item.analisaRisiko"
                                        x-on:input="updateField(section.id, item.id, 'analisaRisiko', $event.target.value)"></x-ui.textarea>
                                </div>

                                <div>
                                    <x-ui.label class="mb-1.5 block text-xs font-semibold text-gray-700">
                                        Rekomendasi <span class="text-red-500">*</span>
                                    </x-ui.label>
                                    <x-ui.textarea rows="2"
                                        class="resize-none border-gray-200 bg-white text-sm text-gray-800 placeholder:text-gray-400"
                                        placeholder="Tuliskan tindakan perbaikan yang disarankan..."
                                        x-bind:value="item.rekomendasi"
                                        x-on:input="updateField(section.id, item.id, 'rekomendasi', $event.target.value)"></x-ui.textarea>
                                </div>

                                <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium"
                                    x-bind:class="riskClass(riskLevel(item))">
                                    <div class="h-2 w-2 shrink-0 rounded-full" x-bind:class="riskDotClass(riskLevel(item))"></div>
                                    <span>
                                        Risiko otomatis:
                                        <span x-text="item.probability"></span> x
                                        <span x-text="item.severity"></span> =
                                        <strong x-text="score(item)"></strong>
                                        - <span x-text="riskLevel(item)"></span>
                                    </span>
                                    <span class="ml-auto hidden text-[10px] opacity-60 sm:block">Dari bobot Kalab</span>
                                </div>
                            </div>

                            <div class="space-y-4 border-t border-gray-100 bg-white/80 p-4"
                                x-show="item.status === 'tidak' && readOnly">
                                <div x-show="(item.fotoDokumentasi ?? []).length > 0">
                                    <p class="mb-2 text-xs font-semibold text-gray-700">Foto Dokumentasi</p>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="photo in (item.fotoDokumentasi ?? [])" :key="photo.id">
                                            <div class="h-20 w-20 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                                                <img x-bind:src="photo.preview" alt="Foto dokumentasi temuan"
                                                    class="h-full w-full object-cover" />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="String(item.analisaRisiko ?? '').trim() !== ''">
                                    <p class="mb-1 text-xs font-semibold text-gray-700">Analisa Risiko</p>
                                    <p class="whitespace-pre-wrap text-sm text-gray-800" x-text="item.analisaRisiko"></p>
                                </div>
                                <div x-show="String(item.rekomendasi ?? '').trim() !== ''">
                                    <p class="mb-1 text-xs font-semibold text-gray-700">Rekomendasi</p>
                                    <p class="whitespace-pre-wrap text-sm text-gray-800" x-text="item.rekomendasi"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </x-ui.card-content>
            </x-ui.card>
        </template>

        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-gray-500"
            x-show="sections.length === 0">
            Belum ada lokasi dalam patroli ini.
        </div>

        <a href="{{ route('patroli.scan', ['type' => 'temuan', 'continue' => 1]) }}"
            x-show="!readOnly"
            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 py-3.5 text-sm font-medium text-gray-500 transition-all duration-150 hover:border-blue-400 hover:bg-blue-50/50 hover:text-blue-600">
            <x-icon name="qr-code" class="h-4 w-4" />
            Tambah Lokasi (scan QR berikutnya)
        </a>

        <template x-teleport="body">
            <div class="fixed bottom-0 left-0 right-0 z-[999] flex flex-col items-center justify-between gap-3 border-t border-gray-200 bg-white px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] sm:flex-row">
                <div class="text-center text-sm text-gray-500 sm:text-left">
                    <template x-if="saveBlockReason() !== ''">
                        <span class="font-medium text-orange-600" x-text="saveBlockReason()"></span>
                    </template>
                    <template x-if="saveBlockReason() === '' && belum() > 0">
                        <span><strong class="text-orange-600" x-text="belum() + ' item'"></strong> belum diperiksa</span>
                    </template>
                    <template x-if="saveBlockReason() === '' && belum() === 0">
                        <span class="font-semibold text-emerald-600">Siap disimpan</span>
                    </template>
                    <span class="ml-2 text-red-600" x-show="temuanKritis().length > 0">
                        · <strong x-text="temuanKritis().length + ' temuan kritis'"></strong>
                    </span>
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
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Inspeksi'"></span>
                    </x-ui.button>
                </div>
            </div>
        </template>

        @include('patroli.partials.temuan.modal-success')
    </div>
@endsection
