@extends('layouts.app')

@section('title', 'Kelola Lokasi - Safety Patrol K3LH')
@section('page_title', 'Kelola Lokasi')

@php
    use Illuminate\Support\Js;

    $lokasiPageConfig = [
        'showForm' => $errors->any(),
        'formNama' => old('nama_lokasi', ''),
        'formJenis' => old('jenis_lokasi', ''),
        'formDeskripsi' => old('deskripsi', ''),
        'storeUrl' => route('inventaris.lokasi.store'),
        'baseUrl' => url('/inventaris/lokasi'),
        'printBatchUrl' => route('inventaris.lokasi.qr.print-batch'),
        'items' => $lokasi->getCollection()->map(
            fn ($row) => $row->only(['id', 'nama_lokasi', 'jenis_lokasi', 'deskripsi']),
        )->values()->all(),
    ];
@endphp

@section('content')
    <div class="space-y-6" x-data="inventarisLokasi({{ Js::from($lokasiPageConfig) }})">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}">
                    <x-ui.button variant="ghost" size="icon" aria-label="Kembali">
                        <x-icon name="arrow-left" class="h-5 w-5" />
                    </x-ui.button>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Kelola Lokasi</h1>
                    <p class="text-sm text-gray-500">CRUD data lokasi/gedung untuk titik inspeksi</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="button" variant="outline" class="border-blue-200 text-blue-700 hover:bg-blue-50"
                    x-show="selectedCount > 0" x-cloak x-on:click="printSelected()">
                    <x-icon name="qr-code" class="h-4 w-4" />
                    <span x-text="'Cetak QR (' + selectedCount + ')'"></span>
                </x-ui.button>
                <x-ui.button class="bg-blue-600 text-white hover:bg-blue-700" x-on:click="openCreate()">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Lokasi
                </x-ui.button>
            </div>
        </div>

        {{-- Modal form tambah / edit --}}
        <div class="fixed inset-0 z-50" x-show="showForm" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="closeForm()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900"
                            x-text="editing ? 'Edit Lokasi' : 'Tambah Lokasi Baru'"></h2>
                        <button type="button" class="text-gray-400 hover:text-gray-600" x-on:click="closeForm()"
                            aria-label="Tutup">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <form method="POST" x-bind:action="formAction()">
                        @csrf
                        <template x-if="editing">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="space-y-4 p-6">
                            <div class="space-y-2">
                                <x-ui.label class="text-gray-700">
                                    Nama Lokasi <span class="text-red-500">*</span>
                                </x-ui.label>
                                <x-ui.input name="nama_lokasi" class="h-11" placeholder="Contoh: Gedung Teknik Sipil"
                                    required maxlength="100" x-model="formNama" />
                                @error('nama_lokasi')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <x-ui.label class="text-gray-700">
                                    Jenis Lokasi <span class="text-red-500">*</span>
                                </x-ui.label>
                                <select name="jenis_lokasi" required x-model="formJenis"
                                    class="flex h-11 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <option value="" disabled>Pilih Jenis Lokasi</option>
                                    @foreach ($jenisOptions as $j)
                                        <option value="{{ $j }}">{{ $j }}</option>
                                    @endforeach
                                </select>
                                @error('jenis_lokasi')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <x-ui.label class="text-gray-700">Deskripsi Lokasi</x-ui.label>
                                <x-ui.textarea name="deskripsi" rows="2"
                                    placeholder="Tambahkan deskripsi singkat (opsional)" maxlength="2000"
                                    x-model="formDeskripsi"></x-ui.textarea>
                            </div>

                            <p class="text-xs text-gray-500" x-show="!editing">
                                QR Code akan dibuat otomatis setelah data disimpan.
                            </p>
                            <p class="text-xs text-gray-500" x-show="editing">
                                QR Code akan diperbarui otomatis jika data lokasi diubah.
                            </p>
                        </div>

                        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 p-6 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="outline" x-on:click="closeForm()">Batal</x-ui.button>
                            <x-ui.button type="submit" class="bg-blue-600 text-white hover:bg-blue-700">
                                Simpan
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal konfirmasi hapus --}}
        <div class="fixed inset-0 z-50" x-show="deleteId" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="cancelDelete()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Lokasi?</h3>
                    <p class="mt-2 text-sm text-gray-600">Data yang sudah terhubung ke APAR atau inspeksi tidak dapat
                        dihapus.</p>
                    <form method="POST" class="mt-4 flex justify-end gap-2" x-bind:action="deleteAction()">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="outline" x-on:click="cancelDelete()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="bg-red-600 text-white hover:bg-red-700">Hapus</x-ui.button>
                    </form>
                </div>
            </div>
        </div>

        <x-ui.card class="border border-gray-200 shadow-sm">
            <x-ui.card-header class="pb-3">
                <form method="GET" action="{{ route('inventaris.lokasi') }}"
                    class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.card-title class="text-base">Daftar Lokasi</x-ui.card-title>
                    <x-ui.input name="q" value="{{ $search }}" placeholder="Cari lokasi..."
                        class="h-9 max-w-xs" />
                </form>
            </x-ui.card-header>

            <x-ui.card-content class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-gray-200 bg-gray-50">
                                <th class="w-10 px-4 py-3">
                                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600"
                                        x-bind:checked="allPageSelected" x-on:change="toggleAllPage()"
                                        aria-label="Pilih semua di halaman ini"
                                        @disabled($lokasi->count() === 0) />
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">No</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Nama Lokasi</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Jenis Lokasi</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lokasi as $idx => $item)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600"
                                            x-bind:checked="isSelected({{ $item->id }})"
                                            x-on:change="toggleId({{ $item->id }})"
                                            aria-label="Pilih {{ $item->nama_lokasi }}" />
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ $lokasi->firstItem() + $idx }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->nama_lokasi }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->jenis_lokasi }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1">
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-500 hover:text-blue-600" aria-label="Edit"
                                                x-on:click="openEditById({{ $item->id }})">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </x-ui.button>
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-500 hover:text-red-600" aria-label="Hapus"
                                                x-on:click="confirmDelete({{ $item->id }})">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </x-ui.button>
                                            <a href="{{ route('inventaris.lokasi.qr.print', $item) }}" target="_blank"
                                                rel="noopener"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-blue-600"
                                                aria-label="Cetak QR Code">
                                                <x-icon name="qr-code" class="h-4 w-4" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Belum ada data lokasi. Klik &quot;Tambah Lokasi&quot; untuk memulai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-ui.server-pagination :paginator="$lokasi" />
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
