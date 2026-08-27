<div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="showUnitForm" x-cloak>
    <div class="fixed inset-0 bg-black/40" x-on:click="showUnitForm = false"></div>
    <div class="relative w-full max-w-lg bg-white rounded-lg border border-gray-200 shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Tambah Unit IPAM Baru</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="space-y-2">
                <x-ui.label class="text-gray-700">Nama Unit <span class="text-red-500">*</span></x-ui.label>
                <x-ui.input placeholder="Contoh: IPAM 1, IPAM Pusat" class="h-11" />
            </div>
            <div class="space-y-2">
                <x-ui.label class="text-gray-700">Deskripsi Unit</x-ui.label>
                <x-ui.textarea placeholder="Deskripsi singkat tentang unit IPAM (opsional)" rows="2"></x-ui.textarea>
            </div>
        </div>
        <div class="p-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <x-ui.button variant="outline" x-on:click="showUnitForm = false">Batal</x-ui.button>
            <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white" x-on:click="showUnitForm = false">Simpan</x-ui.button>
        </div>
    </div>
</div>
