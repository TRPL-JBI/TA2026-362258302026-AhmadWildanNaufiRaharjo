@extends('layouts.app')

@section('title', 'Pedoman SOP - Safety Patrol K3LH')
@section('page_title', 'Pedoman SOP')

@php
    use Illuminate\Support\Js;

    $pageConfig = $sopPageConfig ?? [];
    $canManage = $canManage ?? false;
@endphp

@section('content')
    <div class="mx-auto max-w-5xl space-y-5 pb-10" x-data="sopDokumenPage({{ Js::from($pageConfig) }})">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ route('dashboard') }}" class="mt-0.5 shrink-0">
                    <x-ui.button variant="ghost" size="icon" aria-label="Kembali">
                        <x-icon name="arrow-left" class="h-5 w-5" />
                    </x-ui.button>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Pedoman SOP</h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Standar Operasional Prosedur sebagai pedoman kerja K3LH.
                    </p>
                </div>
            </div>

            @if ($canManage)
                <x-ui.button
                    class="gap-2 self-start bg-blue-600 text-white hover:bg-blue-700"
                    x-on:click="openCreate()"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Dokumen
                </x-ui.button>
            @endif
        </div>

        @if ($canManage)
            <div class="flex items-start gap-2.5 rounded-lg border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-700">
                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>
                    Unggah dokumen <strong>PDF</strong> pedoman SOP (misalnya SOP APAR, SOP Evakuasi, SOP B3)
                    agar Kalab dan Satpam dapat membacanya sebagai panduan kerja.
                    Pastikan judul jelas dan file mudah dibaca sebelum disimpan.
                </span>
            </div>
        @else
            <div class="flex items-start gap-2.5 rounded-lg border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-700">
                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>
                    Halaman ini menampilkan pedoman SOP sebagai panduan kerja.
                    Gunakan tombol <strong>Preview</strong> untuk membaca isi dokumen PDF.
                </span>
            </div>
        @endif

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-header class="pb-3">
                <x-ui.card-title class="text-base">Daftar Pedoman SOP</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Judul</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Deskripsi</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Diperbarui</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Uploader</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in items" :key="item.id">
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium text-gray-900" x-text="item.judul"></td>
                                    <td class="py-3 px-4 text-gray-600">
                                        <span x-text="item.deskripsi || '-'"></span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-gray-500" x-text="item.uploaded_at"></td>
                                    <td class="py-3 px-4 text-gray-500" x-text="item.uploader_nama"></td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1">
                                            <x-ui.button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 p-0 text-gray-400 hover:text-blue-600"
                                                aria-label="Preview"
                                                title="Preview"
                                                x-on:click="openPreview(item)"
                                            >
                                                <x-icon name="eye" class="w-3.5 h-3.5" />
                                            </x-ui.button>
                                            @if ($canManage)
                                                <x-ui.button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 p-0 text-gray-400 hover:text-blue-600"
                                                    aria-label="Edit"
                                                    title="Edit"
                                                    x-on:click="openEdit(item)"
                                                >
                                                    <x-icon name="pencil" class="w-3.5 h-3.5" />
                                                </x-ui.button>
                                                <x-ui.button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Hapus"
                                                    title="Hapus"
                                                    class="h-8 w-8 p-0 text-gray-400 hover:text-red-600"
                                                    x-on:click="confirmDelete(item)"
                                                >
                                                    <x-icon name="trash" class="w-3.5 h-3.5" />
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                    Belum ada dokumen SOP.
                                    @if ($canManage)
                                        Klik <strong>Tambah Dokumen</strong> untuk mengunggah PDF.
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <div
            x-show="showPreview"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-on:keydown.escape.window="closePreview()"
        >
            <div class="flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-900" x-text="previewItem?.judul ?? 'Preview SOP'"></p>
                        <p class="truncate text-xs text-gray-500" x-text="previewItem?.original_filename ?? ''"></p>
                    </div>
                    <x-ui.button variant="ghost" size="icon" aria-label="Tutup" x-on:click="closePreview()">
                        <x-icon name="x" class="h-5 w-5" />
                    </x-ui.button>
                </div>
                <iframe
                    class="h-full w-full flex-1 bg-gray-100"
                    x-bind:src="previewItem?.preview_url ?? ''"
                    title="Preview dokumen SOP"
                ></iframe>
            </div>
        </div>

        @if ($canManage)
            <div
                x-show="showForm"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                x-on:keydown.escape.window="closeForm()"
            >
                <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Edit Dokumen SOP' : 'Tambah Dokumen SOP'"></h2>
                    </div>
                    <form class="space-y-4 px-5 py-4" x-on:submit.prevent="submitForm()">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-gray-700" for="sop-judul">Judul</label>
                            <input
                                id="sop-judul"
                                type="text"
                                class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm"
                                x-model="formJudul"
                                maxlength="150"
                                required
                            >
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-gray-700" for="sop-deskripsi">Deskripsi (opsional)</label>
                            <textarea
                                id="sop-deskripsi"
                                rows="3"
                                class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm"
                                x-model="formDeskripsi"
                                maxlength="2000"
                            ></textarea>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-gray-700" for="sop-file">File PDF</label>
                            <input
                                id="sop-file"
                                type="file"
                                accept="application/pdf,.pdf"
                                class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700"
                                x-on:change="onFileChange($event)"
                                x-bind:required="!editing"
                            >
                            <p class="text-xs text-gray-500" x-show="editing">Kosongkan jika tidak ingin mengganti file PDF.</p>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                            <x-ui.button type="button" variant="outline" x-on:click="closeForm()">Batal</x-ui.button>
                            <x-ui.button type="submit" class="bg-blue-600 text-white hover:bg-blue-700" x-bind:disabled="isSubmitting">
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan'"></span>
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>

            <div
                x-show="deleteItem"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                x-on:keydown.escape.window="cancelDelete()"
            >
                <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus dokumen SOP?</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Dokumen <span class="font-medium" x-text="deleteItem?.judul ?? ''"></span> akan dihapus permanen.
                    </p>
                    <div class="mt-5 flex justify-end gap-2">
                        <x-ui.button type="button" variant="outline" x-on:click="cancelDelete()">Batal</x-ui.button>
                        <x-ui.button
                            type="button"
                            class="bg-red-600 text-white hover:bg-red-700"
                            x-on:click="destroyItem()"
                            x-bind:disabled="isSubmitting"
                        >
                            Hapus
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
