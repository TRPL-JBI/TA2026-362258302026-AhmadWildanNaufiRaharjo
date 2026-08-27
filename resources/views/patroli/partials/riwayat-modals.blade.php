        <div class="fixed inset-0 z-50" x-show="checklistModalOpen" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="closeChecklistModal()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Checklist Lokasi</h3>
                    <form class="mt-4 space-y-3" x-on:submit.prevent="submitChecklist()">
                        <div>
                            <x-ui.label class="mb-1 block text-sm">Lokasi</x-ui.label>
                            <select class="h-10 w-full rounded-md border border-gray-200 px-3 text-sm" x-model="checklistForm.lokasi_id" required>
                                <option value="">Pilih lokasi...</option>
                                <template x-for="loc in overview.temuan.lokasi_tanpa_checklist" :key="loc.id">
                                    <option x-bind:value="loc.id" x-text="loc.label"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-gray-500" x-show="overview.temuan.lokasi_tanpa_checklist.length === 0">
                                Semua lokasi sudah memiliki checklist aktif.
                            </p>
                        </div>
                        <div>
                            <x-ui.label class="mb-1 block text-sm">Nama checklist</x-ui.label>
                            <x-ui.input type="text" x-model="checklistForm.nama_checklist" required maxlength="100"
                                placeholder="Contoh: Checklist Gedung A 2026" />
                        </div>
                        <p class="text-xs text-red-600" x-show="formError" x-text="formError"></p>
                        <div class="flex justify-end gap-2 pt-2">
                            <x-ui.button type="button" variant="outline" x-on:click="closeChecklistModal()">Batal</x-ui.button>
                            <x-ui.button type="submit" class="bg-blue-600 text-white hover:bg-blue-700" x-bind:disabled="submitting">
                                Simpan
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50" x-show="itemModalOpen" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="closeItemModal()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Item Temuan Bahaya</h3>
                    <form class="mt-4 space-y-3" x-on:submit.prevent="submitItem()">
                        <div>
                            <x-ui.label class="mb-1 block text-sm">Checklist</x-ui.label>
                            <select class="h-10 w-full rounded-md border border-gray-200 px-3 text-sm" x-model="itemForm.master_checklist_id" required>
                                <option value="">Pilih checklist...</option>
                                <template x-for="cl in overview.temuan.checklist_options" :key="cl.id">
                                    <option x-bind:value="cl.id" x-text="cl.label"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-gray-500" x-show="overview.temuan.checklist_options.length === 0">
                                Belum ada checklist aktif. Buat checklist terlebih dahulu.
                            </p>
                        </div>
                        <div>
                            <x-ui.label class="mb-1 block text-sm">Nama item</x-ui.label>
                            <x-ui.input type="text" x-model="itemForm.nama_item" required maxlength="200"
                                placeholder="Contoh: Kabel instalasi terkelupas" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-ui.label class="mb-1 block text-sm">Probability (P)</x-ui.label>
                                <select class="h-10 w-full rounded-md border border-gray-200 px-3 text-sm" x-model="itemForm.probability" required>
                                    <template x-for="n in [1,2,3,4,5]" :key="'p'+n">
                                        <option x-bind:value="n" x-text="n"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <x-ui.label class="mb-1 block text-sm">Severity (S)</x-ui.label>
                                <select class="h-10 w-full rounded-md border border-gray-200 px-3 text-sm" x-model="itemForm.severity" required>
                                    <template x-for="n in [1,2,3,4,5]" :key="'s'+n">
                                        <option x-bind:value="n" x-text="n"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <p class="text-xs text-red-600" x-show="formError" x-text="formError"></p>
                        <div class="flex justify-end gap-2 pt-2">
                            <x-ui.button type="button" variant="outline" x-on:click="closeItemModal()">Batal</x-ui.button>
                            <x-ui.button type="submit" class="bg-blue-600 text-white hover:bg-blue-700" x-bind:disabled="submitting">
                                Simpan
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
