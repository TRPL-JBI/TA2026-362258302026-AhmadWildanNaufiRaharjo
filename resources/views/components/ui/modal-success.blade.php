@props([
    /** Nama properti Alpine di parent untuk x-show (contoh: showSuccess) */
    'show' => 'showSuccess',
    'title' => 'Laporan Berhasil Disimpan!',
])

<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
    x-show="{{ $show }}"
    x-cloak
    x-on:click.self="{{ $show }} = false"
    x-on:keydown.escape.window="{{ $show }} = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-200" x-on:click.stop>
        <div class="p-6 text-center space-y-4">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
                <x-icon name="check-circle2" class="w-8 h-8 text-emerald-600" />
            </div>
            <h2 class="text-xl font-bold text-gray-900">{{ $title }}</h2>
            @isset($description)
                <div class="text-sm text-gray-600 space-y-1 text-left sm:text-center">
                    {{ $description }}
                </div>
            @endisset
            <div class="flex flex-col gap-2 pt-2">
                @isset($actions)
                    {{ $actions }}
                @else
                    <x-ui.button variant="outline" class="w-full" type="button" x-on:click="{{ $show }} = false">
                        Tutup
                    </x-ui.button>
                @endisset
            </div>
        </div>
    </div>
</div>
