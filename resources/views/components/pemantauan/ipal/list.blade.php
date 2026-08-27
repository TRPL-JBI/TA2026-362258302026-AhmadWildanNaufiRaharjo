<div x-show="view === 'list'" class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Pemantauan IPAL</h1>
            <p class="mt-0.5 text-sm text-gray-500">Daftar laporan triwulanan kualitas air limbah</p>
        </div>
        <x-ui.button class="w-full gap-2 bg-blue-600 text-white hover:bg-blue-700 sm:w-auto" type="button"
            x-on:click="openForm()">
            <x-icon name="plus" class="h-4 w-4" />
            Buat Laporan Baru
        </x-ui.button>
    </div>

    <x-ui.card class="border-0 shadow-sm">
        <x-ui.card-content class="p-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <x-ui.input type="search" placeholder="Cari periode, status, atau progress..."
                        class="h-10 w-full bg-white pl-9 text-gray-900" x-model="q" />
                </div>
                <select
                    class="h-10 w-full shrink-0 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-44"
                    x-model="filterStatus">
                    <option value="semua">Semua Status</option>
                    <option value="berlangsung">Berlangsung</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card class="border-0 shadow-sm">
        <x-ui.card-content class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Nama Laporan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Progress</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, i) in paginated()" :key="r.id">
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-4 py-3 text-gray-500" x-text="paginationMeta().from + i"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700" x-text="r.tanggal"></td>
                                <td class="max-w-xs px-4 py-3 font-medium text-gray-700" x-text="r.nama_laporan"></td>
                                <td class="max-w-[200px] px-4 py-3 text-gray-600" x-text="r.jumlah"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                        :class="statusClass(r.status)" x-text="r.status"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <button type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                            title="Edit laporan" aria-label="Edit laporan"
                                            x-on:click="openEditReport(r)">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </button>
                                        <button type="button"
                                            class="inline-flex h-8 items-center gap-1 rounded-md px-2 text-xs font-medium text-emerald-600 transition-colors hover:bg-emerald-50"
                                            title="Tandai selesai" x-show="r.status === 'Berlangsung'" x-cloak
                                            x-on:click="tandaiSelesai(r.id)">
                                            <x-icon name="check-circle2" class="h-3.5 w-3.5" />
                                            <span class="hidden sm:inline">Selesai</span>
                                        </button>
                                        <button type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                            title="Hapus laporan" aria-label="Hapus laporan"
                                            x-on:click="hapusLaporan(r)">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered().length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                <span x-show="reports.length === 0">Belum ada laporan IPAL. Mulai dengan tombol Buat Laporan Baru.</span>
                                <span x-show="reports.length > 0">Tidak ada data yang cocok.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <x-ui.list-pagination />
        </x-ui.card-content>
    </x-ui.card>
</div>
