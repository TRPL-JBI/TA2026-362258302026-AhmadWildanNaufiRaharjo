@extends('layouts.app')

@section('title', 'Kelola Inventaris APAR - Safety Patrol K3LH')
@section('page_title', 'Kelola APAR')

@php
    use Illuminate\Support\Js;

    $aparPageConfig = [
        'showForm' => $errors->any(),
        'formLokasiId' => old('lokasi_id', ''),
        'formJenis' => old('jenis_apar', ''),
        'formKapasitas' => old('kapasitas_kg', ''),
        'formTanggalExpired' => old('tanggal_expired', ''),
        'formKeterangan' => old('keterangan', ''),
        'storeUrl' => route('inventaris.apar.store'),
        'baseUrl' => url('/inventaris/apar'),
        'items' => $apar->getCollection()->map(
            fn ($row) => [
                'id' => $row->id,
                'lokasi_id' => $row->lokasi_id,
                'jenis_apar' => $row->jenis_apar,
                'kapasitas_kg' => (string) $row->kapasitas_kg,
                'tanggal_expired' => $row->tanggal_expired->format('Y-m-d'),
                'keterangan' => $row->keterangan,
            ],
        )->values()->all(),
    ];
@endphp

@section('content')
    <div class="space-y-4" x-data="inventarisApar({{ Js::from($aparPageConfig) }})">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Kelola Inventaris APAR</h1>
                <p class="text-sm text-gray-500 mt-0.5">Data unit Alat Pemadam Api Ringan</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                @if ($nearExpiredCount > 0)
                    <x-ui.badge variant="outline" class="bg-orange-100 text-orange-700 border-orange-200 text-xs gap-1">
                        <x-icon name="alert-triangle" class="w-3 h-3" />
                        {{ $nearExpiredCount }} APAR mendekati / sudah expired
                    </x-ui.badge>
                @endif
                <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white gap-2" x-on:click="openCreate()">
                    <x-icon name="plus" class="w-4 h-4" />
                    Tambah APAR
                </x-ui.button>
            </div>
        </div>

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-content class="p-4">
                <form method="GET" action="{{ route('inventaris.apar') }}"
                    class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <x-ui.input name="q" value="{{ $search }}" placeholder="Cari kode APAR atau lokasi..."
                            class="pl-9 h-10 bg-white text-gray-900" />
                    </div>

                    <select name="jenis"
                        class="w-full sm:w-36 h-10 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <option value="">Semua Jenis</option>
                        @foreach ($jenisOptions as $j)
                            <option value="{{ $j }}" @selected($jenis === $j)>{{ $j }}</option>
                        @endforeach
                    </select>

                    <select name="kondisi"
                        class="w-full sm:w-48 h-10 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <option value="">Semua Kondisi Segel</option>
                        @foreach ($kondisiOptions as $k)
                            <option value="{{ $k }}" @selected($kondisi === $k)>{{ $k }}</option>
                        @endforeach
                    </select>

                    <select name="status_expired"
                        class="w-full sm:w-40 h-10 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <option value="">Semua Status</option>
                        <option value="normal" @selected($statusExpired === 'normal')>Normal</option>
                        <option value="warning" @selected($statusExpired === 'warning')>&lt; 30 hari</option>
                        <option value="expired" @selected($statusExpired === 'expired')>Expired</option>
                    </select>

                    <x-ui.button type="submit" variant="outline" class="h-10">Filter</x-ui.button>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        <div class="fixed inset-0 z-50" x-show="showForm" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="closeForm()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 max-h-[90vh] overflow-y-auto">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 p-5">
                        <h2 class="text-lg font-semibold text-gray-900"
                            x-text="editing ? 'Edit APAR' : 'Tambah APAR Baru'"></h2>
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

                        <div class="space-y-4 p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-medium text-gray-600">Jenis APAR <span
                                            class="text-red-500">*</span></x-ui.label>
                                    <select name="jenis_apar" required x-model="formJenis"
                                        class="h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        <option value="" disabled>Pilih jenis</option>
                                        @foreach ($jenisOptions as $j)
                                            <option value="{{ $j }}">{{ $j }}</option>
                                        @endforeach
                                    </select>
                                    @error('jenis_apar')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <x-ui.label class="text-xs font-medium text-gray-600">Kapasitas (kg) <span
                                            class="text-red-500">*</span></x-ui.label>
                                    <x-ui.input type="number" name="kapasitas_kg" step="0.01" min="0.1"
                                        max="999.99" required x-model="formKapasitas"
                                        class="h-10 text-sm bg-white text-gray-900" />
                                    @error('kapasitas_kg')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-2">
                                <x-ui.label class="text-xs font-medium text-gray-600">Lokasi Penempatan <span
                                        class="text-red-500">*</span></x-ui.label>
                                <select name="lokasi_id" required x-model="formLokasiId"
                                    class="h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <option value="" disabled>Pilih lokasi</option>
                                    @forelse ($lokasiOptions as $lok)
                                        <option value="{{ $lok->id }}">{{ $lok->nama_lokasi }}</option>
                                    @empty
                                        <option value="" disabled>Belum ada lokasi. Tambah di menu Kelola Lokasi</option>
                                    @endforelse
                                </select>
                                @error('lokasi_id')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <x-ui.label class="text-xs font-medium text-gray-600">Tanggal Expired <span
                                        class="text-red-500">*</span></x-ui.label>
                                <x-ui.input type="date" name="tanggal_expired" required
                                    x-model="formTanggalExpired" class="h-10 text-sm bg-white text-gray-900" />
                                @error('tanggal_expired')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500">Status kondisi diisi saat patroli APAR, bukan di inventaris.</p>
                            </div>

                            <div class="space-y-2">
                                <x-ui.label class="text-xs font-medium text-gray-600">Keterangan</x-ui.label>
                                <x-ui.textarea name="keterangan" rows="2" maxlength="2000"
                                    placeholder="Catatan tambahan tentang unit APAR ini..."
                                    x-model="formKeterangan"
                                    class="text-sm bg-white resize-none text-gray-900"></x-ui.textarea>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 p-5 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="outline" x-on:click="closeForm()">Batal</x-ui.button>
                            <x-ui.button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white">Simpan</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50" x-show="deleteId" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="cancelDelete()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus APAR?</h3>
                    <p class="mt-2 text-sm text-gray-600">Unit yang sudah memiliki riwayat pemeriksaan tidak dapat dihapus.</p>
                    <form method="POST" class="mt-4 flex justify-end gap-2" x-bind:action="deleteAction()">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="outline" x-on:click="cancelDelete()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="bg-red-600 text-white hover:bg-red-700">Hapus</x-ui.button>
                    </form>
                </div>
            </div>
        </div>

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-content class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">No</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Kode APAR</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Jenis</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Kapasitas</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Lokasi</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Expired</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Kondisi Segel</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($apar as $i => $item)
                                @php
                                    $expStatus = $item->expiredStatus();
                                    $rowClass = match ($expStatus) {
                                        'expired' => 'bg-red-50',
                                        'warning' => 'bg-yellow-50',
                                        default => '',
                                    };
                                    $badge = match ($expStatus) {
                                        'expired' => ['class' => 'bg-red-500 text-white', 'label' => 'EXPIRED'],
                                        'warning' => ['class' => 'bg-yellow-500 text-white', 'label' => '< 30 hari'],
                                        default => null,
                                    };
                                    $kg = rtrim(rtrim(number_format((float) $item->kapasitas_kg, 2, '.', ''), '0'), '.');
                                @endphp
                                <tr class="border-b border-gray-50 hover:bg-gray-50/50 {{ $rowClass }}">
                                    <td class="py-3 px-4 text-gray-500">{{ $apar->firstItem() + $i }}</td>
                                    <td class="py-3 px-4 text-gray-900 font-semibold">{{ $item->kode_apar }}</td>
                                    <td class="py-3 px-4 text-gray-700">{{ $item->jenis_apar }}</td>
                                    <td class="py-3 px-4 text-gray-700">{{ $kg }} Kg</td>
                                    <td class="py-3 px-4 text-gray-700 max-w-xs">{{ $item->lokasi?->nama_lokasi }}</td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="text-gray-700">{{ $item->tanggal_expired->translatedFormat('d F Y') }}</span>
                                        @if ($badge)
                                            <span
                                                class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[9px] {{ $badge['class'] }}">
                                                {{ $badge['label'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($kondisiBadge = $item->kondisiBadge())
                                            <span
                                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $kondisiBadge['class'] }}">
                                                {{ $kondisiBadge['label'] }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex gap-0.5">
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-400 hover:text-blue-600" aria-label="Edit"
                                                x-on:click="openEditById({{ $item->id }})">
                                                <x-icon name="pencil" class="w-3.5 h-3.5" />
                                            </x-ui.button>
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-400 hover:text-red-600" aria-label="Hapus"
                                                x-on:click="confirmDelete({{ $item->id }})">
                                                <x-icon name="trash" class="w-3.5 h-3.5" />
                                            </x-ui.button>
                                            <a href="{{ route('inventaris.apar.qr.print', $item) }}" target="_blank"
                                                rel="noopener"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-purple-600"
                                                aria-label="Cetak QR Code">
                                                <x-icon name="qr-code" class="w-3.5 h-3.5" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-gray-500">
                                        Belum ada data APAR. Pastikan lokasi sudah ada, lalu klik &quot;Tambah APAR&quot;.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-ui.server-pagination :paginator="$apar" />
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
