@props([
    'value' => 0,
])

@php
    $v = max(0, min(100, (float) $value));
@endphp

<div {{ $attributes->class('relative h-4 w-full overflow-hidden rounded-full bg-emerald-100') }}>
    <div class="h-full bg-blue-600 transition-all duration-300" style="width: {{ $v }}%"></div>
</div>
