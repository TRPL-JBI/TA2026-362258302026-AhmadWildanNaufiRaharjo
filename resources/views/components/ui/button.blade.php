@props([
    'variant' => 'primary',
    'size' => 'default',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-semibold transition-colors disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondary' => 'bg-gray-100 text-gray-900 hover:bg-gray-200',
        'outline' => 'border border-gray-200 bg-white text-gray-900 hover:bg-gray-50',
        'ghost' => 'text-gray-900 hover:bg-gray-100',
        'link' => 'text-blue-600 hover:underline underline-offset-4',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
    ];

    $sizes = [
        'default' => 'h-10 px-4 py-2',
        'sm' => 'h-9 px-3',
        'icon' => 'h-10 w-10 p-0',
    ];

    $classes = $base
        . ' ' . ($variants[$variant] ?? $variants['primary'])
        . ' ' . ($sizes[$size] ?? $sizes['default']);
@endphp

<button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
    {{ $slot }}
</button>
