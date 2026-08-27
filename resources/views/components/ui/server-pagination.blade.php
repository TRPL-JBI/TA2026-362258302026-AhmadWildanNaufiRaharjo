@props([
    'paginator',
])

@php
    $btnBase = 'inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-semibold transition-colors';
    $btnNav = $btnBase . ' border-gray-200 bg-white text-gray-900 hover:bg-gray-50';
    $btnNavDisabled = $btnNav . ' pointer-events-none opacity-50';
    $btnPage = $btnBase . ' min-w-9 border-blue-600 bg-blue-600 text-white';
@endphp

@if ($paginator->total() > 0)
    <div class="flex flex-col gap-2 border-t border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-gray-500">
            Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
            dari {{ $paginator->total() }} data
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <button type="button" disabled class="{{ $btnNavDisabled }}">Previous</button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="{{ $btnNav }}">Previous</a>
            @endif

            <button type="button" disabled class="{{ $btnPage }}">{{ $paginator->currentPage() }}</button>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="{{ $btnNav }}">Next</a>
            @else
                <button type="button" disabled class="{{ $btnNavDisabled }}">Next</button>
            @endif
        </div>
    </div>
@endif
