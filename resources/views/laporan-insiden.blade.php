@extends('layouts.app')

@section('title', 'Laporan Insiden Darurat - Safety Patrol K3LH')
@section('page_title', 'Laporan Insiden')

@php
    use Illuminate\Support\Js;

    $pageConfig = $laporanInsidenPageConfig ?? [];
    $jenisOptions = $pageConfig['jenisOptions'] ?? [];
    $lokasiOptions = $pageConfig['lokasiOptions'] ?? [];
@endphp

@section('content')
    <div class="mx-auto max-w-2xl space-y-4" x-data="laporanInsidenPage({{ Js::from($pageConfig) }})">
        <div x-show="showSuccess" x-cloak class="mx-auto mt-12 max-w-lg space-y-4 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                    <x-icon name="check-circle2" class="h-8 w-8 text-emerald-600" />
                </div>
                <h2 class="text-xl font-bold text-gray-900">Laporan Insiden Berhasil Dikirim!</h2>
                <p class="text-sm text-gray-500">
                    Notifikasi telah dikirim ke Petugas K3LH untuk ditindaklanjuti.
                    <span x-show="lastNomor" class="mt-1 block font-medium text-gray-700">
                        Nomor laporan: <span x-text="lastNomor"></span>
                    </span>
                </p>
                <div class="mt-6 flex justify-center gap-3">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 transition-colors hover:bg-gray-50">
                        Kembali ke Beranda
                    </a>
                    <x-ui.button type="button" class="bg-blue-600 text-white hover:bg-blue-700" x-on:click="resetForm()">
                        Buat Laporan Lagi
                    </x-ui.button>
                </div>
        </div>

        <div x-show="!showSuccess" class="space-y-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}">
                        <x-ui.button variant="ghost" size="icon" aria-label="Kembali">
                            <x-icon name="arrow-left" class="h-5 w-5" />
                        </x-ui.button>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Laporan Insiden Darurat</h1>
                        <p class="text-sm text-gray-500">Laporkan insiden yang memerlukan penanganan segera</p>
                    </div>
                </div>

                <form x-on:submit.prevent="submit()">
                    <x-ui.card>
                        <x-ui.card-header class="!p-4 !pb-3">
                            <x-ui.card-title class="flex items-center gap-2 text-base">
                                <x-icon name="alert-triangle" class="h-5 w-5 text-red-500" />
                                Data Insiden
                            </x-ui.card-title>
                        </x-ui.card-header>
                        <x-ui.card-content class="!space-y-4 !p-4">
                            <div class="space-y-1.5">
                                <x-ui.label>Jenis Insiden <span class="text-red-500">*</span></x-ui.label>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($jenisOptions as $jenisOption)
                                        <label
                                            class="flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 transition-colors hover:border-blue-300 hover:bg-blue-50/40"
                                            x-bind:class="jenis === @js($jenisOption) ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : ''">
                                            <input type="radio" name="jenis_insiden" class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                                value="{{ $jenisOption }}" x-model="jenis" />
                                            <span>{{ $jenisOption }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <x-ui.label>Lokasi Kejadian <span class="text-red-500">*</span></x-ui.label>
                                <div class="space-y-2" x-show="!isManualLocation">
                                    <select
                                        class="h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        x-model.number="lokasiId">
                                        <option value="">Pilih lokasi</option>
                                        @foreach ($lokasiOptions as $lokasiOption)
                                            <option value="{{ $lokasiOption['id'] }}">{{ $lokasiOption['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <label class="flex items-center gap-2 text-sm text-gray-600">
                                        <input type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            x-bind:checked="isManualLocation"
                                            x-on:change="setManualLocation($event.target.checked)" />
                                        <span>Lokasi tidak ada di daftar (input manual)</span>
                                    </label>
                                </div>

                                <div class="space-y-2" x-show="isManualLocation">
                                    <x-ui.input class="h-10" placeholder="Masukkan lokasi manual" x-model="manualLocation" />
                                    <x-ui.button type="button" variant="link" class="h-auto p-0 text-sm text-blue-600"
                                        x-on:click="setManualLocation(false)">
                                        Kembali ke pilihan lokasi
                                    </x-ui.button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <x-ui.label>Tanggal Kejadian <span class="text-red-500">*</span></x-ui.label>
                                    <x-ui.input type="date" class="h-10" x-model="tanggal" required />
                                </div>
                                <div class="space-y-1.5">
                                    <x-ui.label>Waktu Kejadian <span class="text-red-500">*</span></x-ui.label>
                                    <x-ui.input type="time" class="h-10" x-model="waktu" required />
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <x-ui.label>Kronologi Kejadian <span class="text-red-500">*</span></x-ui.label>
                                <x-ui.textarea rows="4" placeholder="Jelaskan urutan kejadian secara singkat dan jelas..."
                                    x-model="kronologi" required></x-ui.textarea>
                                <p class="text-right text-xs text-gray-400"><span x-text="kronologi.length"></span> karakter</p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <x-ui.label>Korban</x-ui.label>
                                        <p class="mt-0.5 text-xs text-gray-400">Opsional - tambah jika ada korban</p>
                                    </div>
                                    <x-ui.button type="button" variant="outline" size="sm"
                                        x-on:click="addKorban()"
                                        x-bind:disabled="isSubmitting || korbanList.length >= 20">
                                        <x-icon name="plus" class="h-4 w-4" />
                                        Tambah Korban
                                    </x-ui.button>
                                </div>

                                <p class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-500"
                                    x-show="korbanList.length === 0">
                                    Belum ada data korban. Tekan <span class="font-medium text-gray-700">Tambah Korban</span> jika diperlukan.
                                </p>

                                <div class="space-y-3">
                                    <template x-for="(item, index) in korbanList" :key="item.id">
                                        <div class="rounded-lg border border-gray-200 bg-white p-3 space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    Korban <span x-text="index + 1"></span>
                                                </p>
                                                <x-ui.button type="button" variant="ghost" size="sm"
                                                    class="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    x-on:click="removeKorban(item.id)" x-bind:disabled="isSubmitting">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                    Hapus
                                                </x-ui.button>
                                            </div>

                                            <div class="space-y-1.5">
                                                <x-ui.label>Nama</x-ui.label>
                                                <x-ui.input class="h-10" placeholder="Contoh: Mulyono"
                                                    x-model="item.nama" />
                                            </div>

                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <div class="space-y-1.5">
                                                    <x-ui.label>Usia</x-ui.label>
                                                    <x-ui.input class="h-10" placeholder="Contoh: 20"
                                                        x-model="item.usia" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label>Status</x-ui.label>
                                                    <x-ui.input class="h-10" placeholder="Contoh: Luka Ringan"
                                                        x-model="item.status" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label>Unit / Prodi</x-ui.label>
                                                    <x-ui.input class="h-10" placeholder="Contoh: AGB"
                                                        x-model="item.unitProdi" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-ui.label>Jabatan</x-ui.label>
                                                    <x-ui.input class="h-10" placeholder="Contoh: Mahasiswa"
                                                        x-model="item.jabatan" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <x-ui.label>Foto TKP / Kondisi <span class="text-red-500">*</span></x-ui.label>
                                <div class="rounded-lg border-2 border-dashed border-gray-200 p-4">
                                    <div class="text-center" x-show="fotos.length === 0">
                                        <x-icon name="upload" class="mx-auto mb-1.5 h-7 w-7 text-gray-400" />
                                        <p class="text-sm text-gray-500">Ambil foto langsung atau pilih dari galeri</p>
                                        <p class="mt-1 text-xs text-gray-400">JPG, PNG (Maks. 5MB per file, maks. 10 foto)</p>
                                    </div>

                                    <div class="flex flex-wrap justify-center gap-2" x-show="fotos.length > 0">
                                        <template x-for="photo in fotos" :key="photo.id">
                                            <div class="relative h-16 w-16 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                                                <img x-bind:src="photo.preview" alt="Foto TKP"
                                                    class="h-full w-full object-cover" />
                                                <button type="button"
                                                    class="absolute right-0.5 top-0.5 rounded-full bg-black/60 p-0.5 text-white"
                                                    x-on:click.prevent="removePhoto(photo.id)" x-bind:disabled="isSubmitting"
                                                    aria-label="Hapus foto">
                                                    <x-icon name="x" class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-3 flex justify-center gap-2">
                                        <x-ui.button type="button" variant="outline" size="sm"
                                            x-on:click="pickCamera()" x-bind:disabled="isSubmitting">
                                            <x-icon name="camera" class="h-4 w-4" />
                                            Ambil Foto
                                        </x-ui.button>
                                        <x-ui.button type="button" variant="outline" size="sm"
                                            x-on:click="pickGallery()" x-bind:disabled="isSubmitting">
                                            <x-icon name="upload" class="h-4 w-4" />
                                            Pilih File
                                        </x-ui.button>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card-content>
                    </x-ui.card>

                    <div class="mt-4 flex justify-end gap-3">
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex h-10 items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 transition-colors hover:bg-gray-50">
                            Batal
                        </a>
                        <x-ui.button type="submit" class="bg-red-600 text-white hover:bg-red-700"
                            x-bind:disabled="isSubmitting">
                            <span x-show="!isSubmitting">Kirim Laporan</span>
                            <span x-show="isSubmitting">Mengirim...</span>
                        </x-ui.button>
                    </div>
                </form>
        </div>
    </div>
@endsection
