@extends('layouts.app')

@section('title', 'Kelola Inventaris IPAM - Safety Patrol K3LH')
@section('page_title', 'Kelola IPAM')

@php
    use Illuminate\Support\Js;

    $ipamPageConfig = [
        'showUnitForm' => old('_form') === 'unit' && $errors->any(),
        'showTitikForm' => old('_form') === 'titik' && $errors->any(),
        'formUnitNama' => old('nama_unit', ''),
        'formUnitDeskripsi' => old('deskripsi', ''),
        'formTitikUnitId' => old('unit_ipam_id', ''),
        'formTitikLokasi' => old('titik_lokasi', ''),
        'formTitikDeskripsi' => old('deskripsi', ''),
        'unitStoreUrl' => route('inventaris.ipam.unit.store'),
        'unitBaseUrl' => url('/inventaris/ipam/unit'),
        'titikStoreUrl' => route('inventaris.ipam.titik.store'),
        'titikBaseUrl' => url('/inventaris/ipam/titik'),
        'titikItems' => $titik->getCollection()->map(
            fn ($row) => [
                'id' => $row->id,
                'unit_ipam_id' => $row->unit_ipam_id,
                'titik_lokasi' => $row->titik_lokasi,
                'deskripsi' => $row->deskripsi,
            ],
        )->values()->all(),
    ];
@endphp

@section('content')
    <div class="space-y-6" x-data="inventarisIpam({{ Js::from($ipamPageConfig) }})">
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
                        <x-icon name="arrow-left" class="w-5 h-5" />
                    </x-ui.button>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Kelola Inventaris IPAM</h1>
                    <p class="text-sm text-gray-500">Data unit dan titik sampling pemantauan kualitas air minum</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="outline" class="border-blue-600 text-blue-600 hover:bg-blue-50"
                    x-on:click="openUnitCreate()">
                    <x-icon name="plus" class="w-4 h-4" />
                    Tambah Unit IPAM
                </x-ui.button>
                @if ($units->isEmpty())
                    <x-ui.button class="bg-blue-600 text-white" disabled
                        title="Tambah unit IPAM terlebih dahulu">
                        <x-icon name="plus" class="w-4 h-4" />
                        Tambah Titik IPAM
                    </x-ui.button>
                @else
                    <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white" x-on:click="openTitikCreate()">
                        <x-icon name="plus" class="w-4 h-4" />
                        Tambah Titik IPAM
                    </x-ui.button>
                @endif
            </div>
        </div>

        {{-- Modal unit --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="showUnitForm" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="closeUnitForm()"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl">
                <div class="border-b border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Tambah Unit IPAM Baru</h2>
                </div>
                <form method="POST" action="{{ route('inventaris.ipam.unit.store') }}">
                    @csrf
                    <input type="hidden" name="_form" value="unit">
                    <div class="space-y-4 p-6">
                        <div class="space-y-2">
                            <x-ui.label class="text-gray-700">Nama Unit <span class="text-red-500">*</span></x-ui.label>
                            <x-ui.input name="nama_unit" class="h-11" placeholder="Contoh: IPAM 1, IPAM Pusat"
                                required maxlength="100" x-model="formUnitNama" />
                            @error('nama_unit')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <x-ui.label class="text-gray-700">Deskripsi Unit</x-ui.label>
                            <x-ui.textarea name="deskripsi" rows="2"
                                placeholder="Deskripsi singkat tentang unit IPAM (opsional)" maxlength="2000"
                                x-model="formUnitDeskripsi"></x-ui.textarea>
                        </div>
                        <p class="text-xs text-gray-500">Kode unit (mis. IPM-01) dibuat otomatis oleh sistem.</p>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 p-6 sm:flex-row sm:justify-end">
                        <x-ui.button type="button" variant="outline" x-on:click="closeUnitForm()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="bg-blue-600 text-white hover:bg-blue-700">Simpan</x-ui.button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal titik --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="showTitikForm" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="closeTitikForm()"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl">
                <div class="border-b border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900"
                        x-text="editingTitik ? 'Edit Titik IPAM' : 'Tambah Titik IPAM Baru'"></h2>
                </div>
                <form method="POST" x-bind:action="titikFormAction()">
                    @csrf
                    <input type="hidden" name="_form" value="titik">
                    <input type="hidden" name="_method" value="PUT" x-bind:disabled="!editingTitik">
                    <div class="space-y-4 p-6">
                        <div class="space-y-2">
                            <x-ui.label class="text-gray-700">Unit IPAM <span class="text-red-500">*</span></x-ui.label>
                            <select name="unit_ipam_id" required x-model="formTitikUnitId"
                                class="h-11 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <option value="" disabled>Pilih unit IPAM</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                                @endforeach
                            </select>
                            @error('unit_ipam_id')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <x-ui.label class="text-gray-700">Nama Titik <span class="text-red-500">*</span></x-ui.label>
                            <x-ui.input name="titik_lokasi" class="h-11"
                                placeholder="Contoh: Inlet, Outlet, Bak Filtrasi" required maxlength="50"
                                x-model="formTitikLokasi" />
                            @error('titik_lokasi')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <x-ui.label class="text-gray-700">Deskripsi</x-ui.label>
                            <x-ui.textarea name="deskripsi" rows="2"
                                placeholder="Deskripsi titik sampling (opsional)" maxlength="2000"
                                x-model="formTitikDeskripsi"></x-ui.textarea>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 p-6 sm:flex-row sm:justify-end">
                        <x-ui.button type="button" variant="outline" x-on:click="closeTitikForm()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="bg-blue-600 text-white hover:bg-blue-700">Simpan</x-ui.button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal hapus unit --}}
        <div class="fixed inset-0 z-50" x-show="deleteUnitId" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="cancelDeleteUnit()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Unit IPAM?</h3>
                    <p class="mt-2 text-sm text-gray-600">Unit yang masih memiliki titik sampling tidak dapat dihapus.</p>
                    <form method="POST" class="mt-4 flex justify-end gap-2" x-bind:action="unitDeleteAction()">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="outline" x-on:click="cancelDeleteUnit()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="bg-red-600 text-white hover:bg-red-700">Hapus</x-ui.button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal hapus titik --}}
        <div class="fixed inset-0 z-50" x-show="deleteTitikId" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="cancelDeleteTitik()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Titik IPAM?</h3>
                    <p class="mt-2 text-sm text-gray-600">Titik yang sudah memiliki laporan pemantauan tidak dapat dihapus.</p>
                    <form method="POST" class="mt-4 flex justify-end gap-2" x-bind:action="titikDeleteAction()">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="outline" x-on:click="cancelDeleteTitik()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="bg-red-600 text-white hover:bg-red-700">Hapus</x-ui.button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-content class="p-4">
                <form method="GET" action="{{ route('inventaris.ipam') }}"
                    class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <x-ui.input name="q" value="{{ $search }}" placeholder="Cari titik IPAM..."
                            class="h-10 bg-white text-gray-900" />
                    </div>
                    <select name="unit"
                        class="h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-48"
                        onchange="this.form.submit()">
                        <option value="semua" @selected($filterUnit === 'semua' || $filterUnit === '')>Semua Unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) $filterUnit === (string) $unit->id)>
                                {{ $unit->nama_unit }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        @if ($units->isNotEmpty())
            <x-ui.card class="border border-gray-200 shadow-sm">
                <x-ui.card-header class="pb-3">
                    <x-ui.card-title class="text-base">Daftar Unit IPAM</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-y border-gray-200 bg-gray-50">
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Kode</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Nama Unit</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Jumlah Titik</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($units as $unit)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-500">{{ $unit->kode_unit }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $unit->nama_unit }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $unit->titik_ipam_count }}</td>
                                        <td class="px-4 py-3">
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-500 hover:text-red-600"
                                                aria-label="Hapus unit"
                                                x-on:click="confirmDeleteUnit({{ $unit->id }})">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        @endif

        <x-ui.card class="border border-gray-200 shadow-sm">
            <x-ui.card-header class="pb-3">
                <x-ui.card-title class="text-base">Daftar Titik IPAM</x-ui.card-title>
            </x-ui.card-header>

            <x-ui.card-content class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left font-medium text-gray-600">No</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Unit IPAM</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Nama Titik</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Deskripsi</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($titik as $idx => $item)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-500">{{ $titik->firstItem() + $idx }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->unitIpam?->nama_unit ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $item->titik_lokasi }}</td>
                                    <td class="max-w-48 truncate px-4 py-3 text-gray-500">
                                        {{ $item->deskripsi ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1">
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-500 hover:text-blue-600" aria-label="Edit"
                                                x-on:click="openTitikEditById({{ $item->id }})">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </x-ui.button>
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-500 hover:text-red-600" aria-label="Hapus"
                                                x-on:click="confirmDeleteTitik({{ $item->id }})">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                        @if ($units->isEmpty())
                                            Belum ada unit IPAM. Tambah unit terlebih dahulu, lalu tambah titik sampling.
                                        @else
                                            Belum ada titik IPAM. Klik &quot;Tambah Titik IPAM&quot; untuk memulai.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-ui.server-pagination :paginator="$titik" />
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
