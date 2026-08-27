@props([
    /** @var array<int, array{id:int, nama_unit:string}> */
    'units' => [],
])

<div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="showTitikForm" x-cloak>
    <div class="fixed inset-0 bg-black/40" x-on:click="showTitikForm = false"></div>
    <div class="relative w-full max-w-lg bg-white rounded-lg border border-gray-200 shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Tambah Titik IPAM Baru</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="space-y-2">
                <x-ui.label class="text-gray-700">Unit IPAM <span class="text-red-500">*</span></x-ui.label>
                <select
                    class="h-11 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <option value="" selected disabled>Pilih unit IPAM</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit['id'] }}">{{ $unit['nama_unit'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2">
                <x-ui.label class="text-gray-700">Nama Titik <span class="text-red-500">*</span></x-ui.label>
                <x-ui.input placeholder="Contoh: Inlet, Outlet, Bak Filtrasi" class="h-11" />
            </div>
            <div class="space-y-2">
                <x-ui.label class="text-gray-700">Deskripsi</x-ui.label>
                <x-ui.textarea placeholder="Deskripsi titik sampling (opsional)" rows="2"></x-ui.textarea>
            </div>
        </div>
        <div class="p-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <x-ui.button variant="outline" x-on:click="showTitikForm = false">Batal</x-ui.button>
            <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white" x-on:click="showTitikForm = false">Simpan</x-ui.button>
        </div>
    </div>
</div>
