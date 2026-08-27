{{-- Overlay singkat saat memuat draft lanjutan inspeksi dari riwayat --}}
<template x-if="loadingContinue">
    <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/40 p-4 backdrop-blur-[1px]"
        role="status" aria-live="polite" aria-busy="true">
        <x-ui.card class="w-full max-w-sm border border-gray-200 shadow-2xl" x-on:click.stop>
            <x-ui.card-content class="flex flex-col items-center gap-4 p-8 text-center">
                <div class="h-10 w-10 animate-spin rounded-full border-[3px] border-blue-200 border-t-blue-600" aria-hidden="true"></div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Memuat data inspeksi</p>
                    <p class="mt-1 text-xs text-gray-500">Menyiapkan checklist yang sudah tersimpan…</p>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</template>
