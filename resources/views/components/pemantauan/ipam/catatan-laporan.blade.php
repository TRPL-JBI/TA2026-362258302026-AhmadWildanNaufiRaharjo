<x-ui.card class="border border-gray-200 shadow-sm">
    <x-ui.card-header>
        <x-ui.card-title class="text-base">Catatan Laporan (<span x-text="bulan"></span> <span x-text="tahun"></span>)</x-ui.card-title>
        <p class="text-sm text-gray-500 mt-1">Satu laporan memiliki 1 kendala, 1 rekomendasi, dan 1 kesimpulan untuk periode bulan tersebut.</p>
    </x-ui.card-header>
    <x-ui.card-content class="space-y-4">
        <div class="space-y-2">
            <x-ui.label class="text-gray-700">Kendala</x-ui.label>
            <x-ui.textarea rows="2" class="text-sm resize-none" x-model="notes.kendala"
                placeholder="Tuliskan kendala utama selama periode pemantauan bulan ini..."></x-ui.textarea>
        </div>
        <div class="space-y-2">
            <x-ui.label class="text-gray-700">Rekomendasi</x-ui.label>
            <x-ui.textarea rows="2" class="text-sm resize-none" x-model="notes.rekomendasi"
                placeholder="Tuliskan rekomendasi tindak lanjut selama periode pemantauan bulan ini..."></x-ui.textarea>
        </div>
        <div class="space-y-2">
            <x-ui.label class="text-gray-700">Kesimpulan</x-ui.label>
            <x-ui.textarea rows="2" class="text-sm resize-none" x-model="notes.kesimpulan"
                placeholder="Kesimpulan umum kelayakan kualitas air minum untuk periode ini..."></x-ui.textarea>
        </div>
    </x-ui.card-content>
</x-ui.card>

