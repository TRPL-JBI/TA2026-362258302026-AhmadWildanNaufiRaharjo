<template x-if="showSuccess">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <x-ui.card class="w-full max-w-sm border-0 shadow-2xl">
            <x-ui.card-content class="space-y-4 p-6 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                    <x-icon name="check-circle2" class="h-8 w-8 text-emerald-600" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Inspeksi Berhasil Disimpan!</h2>
                    <p class="mt-1 text-sm text-gray-500">Data telah tersimpan ke database</p>
                </div>
                <div class="space-y-2 rounded-xl bg-gray-50 p-4 text-left text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Lokasi diinspeksi</span>
                        <strong class="text-gray-800" x-text="sections.length + ' lokasi'"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total item</span>
                        <strong class="text-gray-800" x-text="totalItems() + ' item'"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-emerald-600">Sesuai</span>
                        <strong class="text-emerald-700" x-text="doneYa() + ' item'"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-red-500">Tidak Sesuai</span>
                        <strong class="text-red-700" x-text="doneTidak() + ' item'"></strong>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-1" x-show="temuanKritis().length > 0">
                        <span class="flex items-center gap-1 text-orange-600">
                            <x-icon name="alert-triangle" class="h-3.5 w-3.5" />
                            Temuan Kritis
                        </span>
                        <strong class="text-orange-700" x-text="temuanKritis().length + ' item'"></strong>
                    </div>
                </div>
                <p class="rounded-lg border border-orange-100 bg-orange-50 px-3 py-2 text-xs text-orange-600"
                    x-show="temuanKritis().length > 0"
                    x-text="temuanKritis().length + ' temuan kritis otomatis diteruskan ke Admin untuk ditindaklanjuti'"></p>
                <div class="flex flex-col gap-2 pt-1">
                    <a href="{{ route('patroli.riwayat') }}"
                        class="inline-flex h-10 w-full items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                        Lihat Riwayat
                    </a>
                    <x-ui.button variant="ghost" type="button" class="w-full text-sm text-gray-400" x-on:click="showSuccess = false">
                        Kembali ke Beranda
                    </x-ui.button>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</template>
