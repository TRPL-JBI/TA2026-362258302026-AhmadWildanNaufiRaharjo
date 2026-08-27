@props([
    'min' => 2000,
    'max' => 2100,
    'size' => 'default',
])

@php
    $triggerSizes = [
        'default' => 'h-11',
        'sm' => 'h-10',
    ];

    $triggerClass = 'flex w-full items-center justify-between gap-2 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 '
        . ($triggerSizes[$size] ?? $triggerSizes['default']);
@endphp

<div
    x-data="yearPicker({ min: {{ (int) $min }}, max: {{ (int) $max }} })"
    x-modelable="value"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    {{ $attributes->except(['min', 'max', 'size'])->class('relative') }}
>
    <button
        type="button"
        class="{{ $triggerClass }}"
        x-on:click="toggle()"
        x-bind:aria-expanded="open"
        aria-haspopup="dialog"
    >
        <span x-text="value || 'Pilih tahun'" class="truncate"></span>
        <x-icon name="calendar" class="h-4 w-4 shrink-0 text-gray-400" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute left-0 right-0 z-50 mt-1 min-w-[17rem] rounded-md border border-gray-200 bg-white p-3 shadow-lg"
        role="dialog"
        aria-label="Pilih tahun"
    >
        <div class="mb-3 flex items-center justify-between gap-2">
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                x-on:click="prevPage()"
                x-bind:disabled="!canPrev()"
                aria-label="Tahun sebelumnya"
            >
                <x-icon name="arrow-left" class="h-4 w-4" />
            </button>
            <span class="text-sm font-semibold text-gray-900" x-text="viewLabel"></span>
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                x-on:click="nextPage()"
                x-bind:disabled="!canNext()"
                aria-label="Tahun berikutnya"
            >
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </div>

        <div class="grid grid-cols-4 gap-1">
            <template x-for="year in years" x-bind:key="year">
                <button
                    type="button"
                    class="rounded-md px-2 py-2 text-sm transition-colors"
                    x-bind:class="yearButtonClass(year)"
                    x-on:click="selectYear(year)"
                    x-text="year"
                ></button>
            </template>
        </div>
    </div>
</div>
