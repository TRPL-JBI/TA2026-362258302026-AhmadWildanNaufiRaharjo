@props([
    'variant' => 'default',
])

@php
    $base = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors';

    $variants = [
        'default' => 'border-transparent bg-blue-600 text-white hover:bg-blue-700',
        'secondary' => 'border-transparent bg-emerald-600 text-white hover:bg-emerald-700',
        'destructive' => 'border-transparent bg-red-600 text-white hover:bg-red-700',
        'outline' => 'text-gray-900 border-gray-200 bg-white',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['default']);
@endphp

<div {{ $attributes->class($classes) }}>
    {{ $slot }}
</div>
