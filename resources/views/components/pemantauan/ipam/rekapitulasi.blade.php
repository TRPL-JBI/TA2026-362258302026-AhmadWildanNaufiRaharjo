<x-ui.card class="bg-gray-50 border border-gray-200 shadow-sm">
    <x-ui.card-header>
        <x-ui.card-title class="text-base">
            Rekapitulasi Bulanan - <span x-text="bulan"></span> <span x-text="tahun"></span>
        </x-ui.card-title>
    </x-ui.card-header>
    <x-ui.card-content>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-white border-y border-gray-200">
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Unit IPAM</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Minggu</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Jumlah Titik</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Baik</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Tidak Baik</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Rata pH</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Rata ALT</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Salmonella (+)</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in rekapRowsList" :key="row.key">
                        <tr class="border-b border-gray-100 bg-white">
                            <td class="px-3 py-2 font-medium" x-text="row.unit"></td>
                            <td class="px-3 py-2" x-text="`Minggu ${row.minggu}`"></td>
                            <td class="px-3 py-2" x-text="row.jumlahTitik"></td>
                            <td class="px-3 py-2" x-text="row.baik"></td>
                            <td class="px-3 py-2" x-text="row.tidakBaik"></td>
                            <td class="px-3 py-2" x-text="row.rataPh"></td>
                            <td class="px-3 py-2" x-text="row.rataAlt"></td>
                            <td class="px-3 py-2" x-text="row.salmonellaPositif"></td>
                        </tr>
                    </template>
                    <tr x-show="rekapRowsList.length === 0">
                        <td colspan="8" class="px-3 py-8 text-center text-sm text-gray-500">Belum ada data rekap.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-2">* Rekapitulasi dihitung otomatis berdasarkan data yang diisi.</p>
    </x-ui.card-content>
</x-ui.card>

