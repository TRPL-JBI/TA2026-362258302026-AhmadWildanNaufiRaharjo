@extends('layouts.app')

@section('title', 'Scan QR Patroli - Safety Patrol K3LH')
@section('page_title', 'Scan QR')

@section('content')
    <div class="mx-auto max-w-lg space-y-6"
        x-data="patroliScanPage({{ \Illuminate\Support\Js::from($scanOpts + ['manualItems' => $manualItems]) }})"
        x-init="() => { initScanner(); return () => destroyScanner(); }">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Scan QR Code</h1>
            <p class="mt-1 text-sm text-gray-500">
                @if ($scanType === 'apar')
                    Arahkan kamera ke QR Code APAR
                @elseif ($scanType === 'temuan')
                    Arahkan kamera ke QR Code lokasi
                @else
                    Arahkan kamera ke QR Code lokasi atau APAR
                @endif
            </p>
        </div>

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

        <div class="rounded-lg border px-3.5 py-2.5 text-sm"
            x-show="pageAlert"
            x-cloak
            x-bind:class="pageAlert?.type === 'error'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
            <div class="flex items-start gap-2">
                <x-icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0" x-show="pageAlert?.type === 'error'" />
                <x-icon name="check-circle2" class="mt-0.5 h-4 w-4 shrink-0" x-show="pageAlert?.type === 'success'" />
                <p class="min-w-0 flex-1" x-text="pageAlert?.message"></p>
                <button type="button" class="shrink-0 text-current opacity-60 hover:opacity-100" x-on:click="pageAlert = null" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </div>

        @if ($scanType === 'temuan' && $continuePatrol)
            <div class="flex items-start gap-2.5 rounded-lg border border-amber-100 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-800">
                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>Menambah lokasi ke patroli yang sedang berlangsung. Checklist hasil scan akan ditambahkan di bawah data yang sudah diisi.</span>
            </div>
        @elseif ($scanType === 'apar' && $continuePatrol)
            <div class="flex items-start gap-2.5 rounded-lg border border-amber-100 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-800">
                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>Menambah APAR ke pemeriksaan yang sedang berlangsung. Unit hasil scan akan ditambahkan di bawah data yang sudah diisi.</span>
            </div>
        @elseif ($scanType === 'temuan')
            <div class="flex items-start gap-2.5 rounded-lg border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-700">
                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>Scan QR lokasi dari inventaris untuk membuka checklist temuan bahaya. Pastikan lokasi sudah memiliki checklist aktif.</span>
            </div>
        @elseif ($scanType === 'apar')
            <div class="flex items-start gap-2.5 rounded-lg border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-700">
                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>Scan QR APAR dari inventaris untuk membuka form pemeriksaan APAR.</span>
            </div>
        @else
            <div class="flex items-start gap-2.5 rounded-lg border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-700">
                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>Patroli dilakukan 3 kali setahun (per caturwulan). Scan QR <strong>lokasi</strong> untuk checklist temuan bahaya, atau QR <strong>APAR</strong> untuk pemeriksaan APAR.</span>
            </div>
        @endif

        <x-ui.card class="overflow-hidden border-0 shadow-sm">
            <x-ui.card-content class="relative p-0">
                {{-- viewport kamera 4:3 --}}
                <div class="relative aspect-[4/3] bg-gray-900">
                    {{-- Kontainer pemindaian (video diisi html5-qrcode) --}}
                    <div id="patroli-qr-reader" class="absolute inset-0 patroli-qr-reader-root"
                        x-bind:class="manualModalOpen ? 'pointer-events-none opacity-0' : ''"></div>

                    {{-- Bingkai panduan --}}
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="relative h-56 w-56">
                            <div class="absolute left-0 top-0 h-8 w-8 rounded-tl-lg border-l-[3px] border-t-[3px] border-sky-400"></div>
                            <div class="absolute right-0 top-0 h-8 w-8 rounded-tr-lg border-r-[3px] border-t-[3px] border-sky-400"></div>
                            <div class="absolute bottom-0 left-0 h-8 w-8 rounded-bl-lg border-b-[3px] border-l-[3px] border-sky-400"></div>
                            <div class="absolute bottom-0 right-0 h-8 w-8 rounded-br-lg border-b-[3px] border-r-[3px] border-sky-400"></div>
                            <div class="patroli-scanline absolute left-4 right-4 top-1/2 h-0.5 -translate-y-1/2 bg-sky-400/60 shadow-[0_0_12px_rgba(56,189,248,.6)]"></div>
                        </div>
                    </div>

                    {{-- Overlay teks atas --}}
                    <div class="pointer-events-none absolute inset-x-0 top-6 text-center">
                        <p class="text-sm font-medium text-white">Arahkan kamera ke QR Code</p>
                        <p class="mt-1 text-xs text-gray-400">QR Code akan otomatis terdeteksi</p>
                    </div>

                    {{-- Kontrol --}}
                    <div class="absolute bottom-6 left-0 right-0 z-20 flex justify-center gap-6 px-4">
                        <button type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-white/30 disabled:pointer-events-none disabled:opacity-30"
                            x-on:click="toggleTorch()"
                            x-bind:disabled="initializing || !torchSupported || scanError"
                            aria-label="Senter / flash">
                            <x-icon name="flashlight" class="h-5 w-5" />
                        </button>
                        <button type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-white/30 disabled:pointer-events-none disabled:opacity-30"
                            x-on:click="flipCamera()"
                            x-bind:disabled="initializing || !canFlipCamera || scanError"
                            aria-label="Ganti kamera">
                            <x-icon name="switch-camera" class="h-5 w-5" />
                        </button>
                    </div>

                    {{-- Status muat / error --}}
                    <template x-if="initializing || resolving">
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/50">
                            <p class="text-sm text-white" x-text="resolving ? 'Memvalidasi QR…' : 'Memuat kamera…'"></p>
                        </div>
                    </template>

                    <div class="pointer-events-none absolute bottom-24 left-0 right-0 z-20 px-4 text-center" x-show="scanError && !initializing">
                        <p class="rounded-lg bg-red-950/90 px-3 py-2 text-xs text-red-100" x-text="scanError"></p>
                        <button type="button"
                            class="pointer-events-auto mt-2 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-medium text-white hover:bg-white/25"
                            x-on:click="initScanner()">
                            Coba lagi
                        </button>
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        @if (false)
        <div class="space-y-3">
            <p class="text-sm font-medium text-gray-700">Demo: Simulasi Hasil Scan</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <a href="{{ route('patroli.temuan', ['q' => json_encode(['id' => 10, 'nama' => 'Lab Kimia - Gedung C Lt.1', 'checklist' => 'Checklist Lab Kimia'])]) }}"
                    class="inline-flex min-h-[3.75rem] w-full items-center justify-start gap-3 rounded-md border border-blue-200 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 shadow-sm transition-colors hover:bg-blue-50">
                    <x-icon name="map-pin" class="h-5 w-5 shrink-0 text-blue-600" />
                    <span class="min-w-0">
                        <span class="block font-medium">Scan QR Lokasi</span>
                        <span class="mt-0.5 block text-xs font-normal text-gray-500">Buka Form Temuan Bahaya</span>
                    </span>
                </a>
                <a href="{{ route('patroli.apar', ['q' => json_encode(['kodeApar' => 'APAR-LABKIMIA-001', 'lokasiApar' => 'Lab Kimia - Gedung C Lt.1', 'jenisKapasitas' => 'Powder - 6 Kg', 'tanggalExpired' => '01 September 2025'])]) }}"
                    class="inline-flex min-h-[3.75rem] w-full items-center justify-start gap-3 rounded-md border border-emerald-200 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 shadow-sm transition-colors hover:bg-emerald-50">
                    <x-icon name="shield-check" class="h-5 w-5 shrink-0 text-emerald-600" />
                    <span class="min-w-0">
                        <span class="block font-medium">Scan QR APAR</span>
                        <span class="mt-0.5 block text-xs font-normal text-gray-500">Buka Form Pemantauan APAR</span>
                    </span>
                </a>
            </div>
        </div>
        @endif

        <p class="text-center text-sm text-gray-500">
            Scan gagal?
            <button type="button" class="font-medium text-blue-600 hover:underline" x-on:click="openManualModal()"
                x-text="manualButtonLabel()"></button>
        </p>

        <template x-if="manualModalOpen">
            <div class="fixed inset-0 z-[60] flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" x-on:click.self="closeManualModal()">
                <x-ui.card class="w-full rounded-t-2xl border-0 shadow-2xl sm:max-w-md sm:rounded-lg" x-on:click.stop>
                    <x-ui.card-header class="border-b border-gray-100 pb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <x-ui.card-title class="text-base font-bold text-gray-900" x-text="manualTitle()"></x-ui.card-title>
                                <p class="mt-0.5 text-xs text-gray-500" x-text="manualDescription()"></p>
                            </div>
                            <x-ui.button variant="ghost" size="icon" type="button" class="h-8 w-8 shrink-0 text-gray-400" aria-label="Tutup"
                                x-on:click="closeManualModal()">
                                <x-icon name="x" class="h-4 w-4" />
                            </x-ui.button>
                        </div>
                    </x-ui.card-header>
                    <x-ui.card-content class="space-y-3 p-5">
                        <div class="relative">
                            <x-icon name="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                            <x-ui.input class="h-9 bg-gray-50 pl-9 text-sm" x-bind:placeholder="manualSearchPlaceholder()"
                                x-model="manualQuery" />
                        </div>
                        <div class="max-h-72 space-y-2 overflow-y-auto">
                            <template x-for="item in filteredManualItems()" :key="manualItemKey(item)">
                                <button type="button"
                                    class="flex w-full items-start gap-3 rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-left transition-colors hover:border-blue-200 hover:bg-blue-50/40"
                                    x-on:click="navigateWithManualItem(item)">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                        x-bind:class="itemKind(item) === 'apar' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'">
                                        <x-icon name="shield-check" class="h-4 w-4" x-show="itemKind(item) === 'apar'" />
                                        <x-icon name="map-pin" class="h-4 w-4" x-show="itemKind(item) === 'lokasi'" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-gray-800" x-text="item.label"></span>
                                        <span class="mt-0.5 block truncate text-[11px] text-gray-500" x-text="item.subLabel"></span>
                                    </span>
                                </button>
                            </template>
                            <div class="py-6 text-center text-sm text-gray-400" x-show="filteredManualItems().length === 0">
                                Tidak ada data yang cocok.
                            </div>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>
            </div>
        </template>
    </div>
@endsection
