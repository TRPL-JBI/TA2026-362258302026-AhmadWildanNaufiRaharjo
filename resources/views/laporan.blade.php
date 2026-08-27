@extends('layouts.app')

@section('title', 'Laporan - Safety Patrol K3LH')
@section('page_title', 'Laporan')

@php
    use Illuminate\Support\Js;
@endphp

@section('content')
    <div class="space-y-6" x-data="laporanListPage({{ Js::from($generatedReports) }})">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}">
                <x-ui.button variant="ghost" size="icon" aria-label="Kembali">
                    <x-icon name="arrow-left" class="w-5 h-5" />
                </x-ui.button>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Laporan</h1>
                <p class="text-sm text-gray-500">Cari, preview, dan unduh laporan yang sudah di-generate</p>
            </div>
        </div>

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-content class="p-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <x-ui.input placeholder="Cari nama laporan..." class="pl-9 h-10 bg-white text-gray-900"
                            x-model="q" />
                    </div>
                    <select
                        class="w-full sm:w-48 h-10 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        x-model="jenis">
                        <option value="semua">Semua Jenis</option>
                        <option value="patroli">Patroli</option>
                        <option value="apar">APAR</option>
                        <option value="ipal">IPAL</option>
                        <option value="ipam">IPAM</option>
                        <option value="limbah b3">Limbah B3</option>
                        <option value="sop">SOP</option>
                        <option value="insiden">Insiden</option>
                    </select>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="border border-gray-200 shadow-sm">
            <x-ui.card-header class="pb-3">
                <x-ui.card-title class="text-base">Daftar Laporan</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-y border-gray-200">
                                <th class="text-left px-4 py-3 font-medium text-gray-600">No</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Nama Laporan</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Jenis</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Petugas</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Tanggal</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Format</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Ukuran</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(r, idx) in paginated()" :key="r.id">
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-500" x-text="paginationMeta().from + idx"></td>
                                    <td class="px-4 py-3 font-medium text-gray-900" x-text="r.nama"></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="r.jenis"></td>
                                    <td class="px-4 py-3 text-gray-500" x-text="r.petugas"></td>
                                    <td class="px-4 py-3 text-gray-500" x-text="r.tanggal"></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="r.format"></td>
                                    <td class="px-4 py-3 text-gray-500" x-text="r.size"></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1">
                                            <x-ui.button variant="ghost" size="icon"
                                                class="h-8 w-8 p-0 text-gray-400 hover:text-blue-600" type="button"
                                                title="Preview" aria-label="Preview laporan"
                                                x-on:click="openPreview(r)">
                                                <x-icon name="eye" class="w-4 h-4" />
                                            </x-ui.button>
                                            <a :href="r.download_url"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                                title="Download" aria-label="Download">
                                                <x-icon name="download" class="w-4 h-4" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filtered().length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                                    <span x-show="reports.length === 0">Belum ada laporan yang di-generate.</span>
                                    <span x-show="reports.length > 0">Tidak ada laporan yang cocok.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <x-ui.list-pagination />
            </x-ui.card-content>
        </x-ui.card>

        <div x-show="previewOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            x-on:click.self="closePreview()" x-on:keydown.escape.window="closePreview()">
            <x-ui.card class="flex h-[92vh] w-full max-w-6xl flex-col border border-gray-200 shadow-2xl"
                x-on:click.stop>
                <x-ui.card-header class="shrink-0 border-b border-gray-100 pb-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <x-ui.card-title class="text-lg text-gray-900">Preview Laporan</x-ui.card-title>
                            <p class="mt-0.5 truncate text-sm text-gray-500" x-text="previewTitle"></p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <a :href="previewDownloadUrl"
                                class="inline-flex h-9 items-center gap-2 rounded-md bg-blue-600 px-3 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                                x-show="previewDownloadUrl">
                                <x-icon name="download" class="h-4 w-4" />
                                Unduh
                            </a>
                            <x-ui.button variant="ghost" size="icon" class="h-8 w-8 p-0 text-gray-400" type="button"
                                aria-label="Tutup" x-on:click="closePreview()">
                                <x-icon name="x" class="w-4 h-4" />
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card-header>
                <x-ui.card-content class="flex min-h-0 flex-1 flex-col p-0">
                    <div class="flex items-center justify-center py-16 text-sm text-gray-500" x-show="previewLoading">
                        Memuat preview laporan...
                    </div>
                    <div class="px-5 py-10 text-center text-sm text-red-600" x-show="previewError && !previewLoading"
                        x-text="previewError"></div>
                    <div class="laporan-preview-stage min-h-0 flex-1 overflow-y-auto overflow-x-auto bg-gray-100"
                        x-show="!previewLoading && !previewError">
                        <div x-ref="previewDocxWrap" class="laporan-preview-docx-wrap">
                            <div x-ref="previewStyle"></div>
                            <div x-ref="previewBody" class="laporan-preview-docx py-6"></div>
                        </div>
                        <div x-ref="previewXlsxWrap" class="hidden laporan-preview-xlsx-wrap p-4">
                            <div x-ref="previewXlsxBody"></div>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <style>
            .laporan-preview-docx .docx-wrapper {
                margin: 0 auto;
                background: #fff;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
            }

            .laporan-preview-docx section.docx {
                margin-bottom: 24px;
                overflow: visible !important;
            }

            .laporan-preview-xlsx-wrap {
                width: 100%;
                min-width: 0;
            }

            .laporan-xlsx-sheet + .laporan-xlsx-sheet {
                margin-top: 24px;
            }

            .laporan-xlsx-sheet {
                width: 100%;
                overflow-x: auto;
            }

            .laporan-xlsx-sheet-inner {
                width: max-content;
                min-width: 100%;
            }

            .laporan-xlsx-sheet-title {
                margin-bottom: 8px;
                font-size: 0.875rem;
                font-weight: 600;
                color: #334155;
            }

            .laporan-xlsx-table {
                border-collapse: collapse;
                table-layout: fixed;
                background: #fff;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            }

            .laporan-xlsx-table td {
                padding: 4px 8px;
                overflow: hidden;
                text-overflow: ellipsis;
                border: 1px solid #e2e8f0;
                white-space: pre-wrap;
                word-break: break-word;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                max-height: 200px;
            }

            .laporan-xlsx-images-section {
                margin-top: 16px;
                border-radius: 12px;
                background: #fff;
                padding: 16px;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            }

            .laporan-xlsx-images-title {
                margin-bottom: 12px;
                font-size: 0.875rem;
                font-weight: 600;
                color: #0f172a;
            }

            .laporan-xlsx-images-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 12px;
            }

            .laporan-xlsx-image-card {
                margin: 0;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
            }

            .laporan-xlsx-image {
                display: block;
                width: 100%;
                aspect-ratio: 4 / 3;
                object-fit: cover;
                background: #e2e8f0;
            }

            .laporan-xlsx-image-caption {
                padding: 8px 10px;
                font-size: 0.75rem;
                color: #475569;
            }
        </style>
    </div>
@endsection
