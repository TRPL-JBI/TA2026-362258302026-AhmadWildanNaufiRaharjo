@props([
    'type' => 'text',
])

@php
    $base = 'flex w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm';
@endphp

<input type="{{ $type }}" {{ $attributes->class($base) }} />
