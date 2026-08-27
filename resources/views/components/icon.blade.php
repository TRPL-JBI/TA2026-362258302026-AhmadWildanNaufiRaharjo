@props([
    'name',
])

@php
    $base = 'w-5 h-5';
    $providedClass = (string) ($attributes->get('class') ?? '');
    $hasCustomSize = preg_match('/(^|\s)(h-|w-|size-)/', $providedClass) === 1;
    $cls = trim(($hasCustomSize ? '' : $base . ' ') . $providedClass);
@endphp

@switch($name)
    @case('shield')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
        </svg>
    @break

    @case('home')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9.5l9-7 9 7V20a2 2 0 0 1-2 2h-5v-7H10v7H5a2 2 0 0 1-2-2V9.5z"></path>
        </svg>
    @break

    @case('map-pin')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"></path>
        </svg>
    @break

    @case('alert-triangle')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.29 3.86a2 2 0 0 1 3.42 0l8 13.85A2 2 0 0 1 20 21H4a2 2 0 0 1-1.71-3.29l8-13.85z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17h.01"></path>
        </svg>
    @break

    @case('file-text')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2v6h6"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 13H8"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17H8"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9H8"></path>
        </svg>
    @break

    @case('search')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35"></path>
        </svg>
    @break

    @case('droplets')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2s6 6.5 6 12a6 6 0 1 1-12 0c0-5.5 6-12 6-12z"></path>
        </svg>
    @break

    @case('beaker')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 2h12"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v6l-4 7a4 4 0 0 0 3.5 6h9A4 4 0 0 0 20 15l-4-7V2"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8"></path>
        </svg>
    @break

    @case('flask')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 2v6.5L4.5 19a2.5 2.5 0 0 0 2.2 3.5h10.6a2.5 2.5 0 0 0 2.2-3.5L14 8.5V2"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8"></path>
        </svg>
    @break

    @case('check-square')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
    @break

    @case('bar-chart')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17V9"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17V5"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17v-7"></path>
        </svg>
    @break

    @case('bell')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a3 3 0 0 0 6 0"></path>
        </svg>
    @break

    @case('user')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path>
        </svg>
    @break

    @case('log-out')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l5-5-5-5"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12H9"></path>
        </svg>
    @break

    @case('menu')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 18h16"></path>
        </svg>
    @break

    @case('x')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12"></path>
        </svg>
    @break

    @case('chevron-down')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
        </svg>
    @break

    @case('arrow-left')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l-7-7 7-7"></path>
        </svg>
    @break

    @case('arrow-right')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5l7 7-7 7"></path>
        </svg>
    @break

    @case('plus')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
        </svg>
    @break

    @case('pencil')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
        </svg>
    @break

    @case('trash')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11v6"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 11v6"></path>
        </svg>
    @break

    @case('qr-code')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h7v7H3V3z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7h-7V3z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 14h7v7H3v-7z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 14h1v1h-1v-1z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14h4v4h-4v-4z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 17h1v4h-1v-4z"></path>
        </svg>
    @break

    @case('history')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 3-6.7"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v6h6"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 3"></path>
        </svg>
    @break

    @case('download')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3"></path>
        </svg>
    @break

    @case('calendar')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"></path>
        </svg>
    @break

    @case('clock')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
        </svg>
    @break

    @case('upload')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l-5-5-5 5"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12"></path>
        </svg>
    @break

    @case('image')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16v14H4z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15l4-4 4 4 4-4 4 4"></path>
            <circle cx="9" cy="9" r="2" stroke-width="2"></circle>
        </svg>
    @break

    @case('eye')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
        </svg>
    @break

    @case('check-circle2')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0z"></path>
        </svg>
    @break

    @case('file-spreadsheet')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2v6h6"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 10v10"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10v10"></path>
        </svg>
    @break

    @case('toggle-left')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <rect x="2" y="7" width="16" height="10" rx="5" ry="5" stroke-width="2"></rect>
            <circle cx="7" cy="12" r="3" fill="currentColor" stroke="none"></circle>
        </svg>
    @break

    @case('toggle-right')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <rect x="2" y="7" width="16" height="10" rx="5" ry="5" stroke-width="2"></rect>
            <circle cx="13" cy="12" r="3" fill="currentColor" stroke="none"></circle>
        </svg>
    @break

    @case('chevron-up')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 15l-6-6-6 6"></path>
        </svg>
    @break

    @case('grip-vertical')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <circle cx="9" cy="5" r="1" fill="currentColor" stroke="none"></circle>
            <circle cx="15" cy="5" r="1" fill="currentColor" stroke="none"></circle>
            <circle cx="9" cy="12" r="1" fill="currentColor" stroke="none"></circle>
            <circle cx="15" cy="12" r="1" fill="currentColor" stroke="none"></circle>
            <circle cx="9" cy="19" r="1" fill="currentColor" stroke="none"></circle>
            <circle cx="15" cy="19" r="1" fill="currentColor" stroke="none"></circle>
        </svg>
    @break

    @case('save')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-8H7v8"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3v5h8"></path>
        </svg>
    @break

    @case('info')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8h.01"></path>
        </svg>
    @break

    @case('clipboard-list')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"></path>
        </svg>
    @break

    @case('shield-check')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
        </svg>
    @break

    @case('shield-alert')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v5"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16h.01"></path>
        </svg>
    @break

    @case('camera')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h3l2-3h6l2 3h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"></path>
            <circle cx="12" cy="13" r="4" stroke-width="2"></circle>
        </svg>
    @break

    @case('package')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8l-9-5-9 5 9 5 9-5z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8v8l9 5 9-5V8"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13v8"></path>
        </svg>
    @break

    @case('flashlight')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 2h12v16H10l-4 4V2z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6h6"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6"></path>
        </svg>
    @break

    @case('switch-camera')
        <svg {{ $attributes->merge(['class' => $cls, 'fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19H7a5 5 0 0 1-5-5V12"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 11v3a6 6 0 0 1-6 6h-1"></path>
            <circle cx="12" cy="12" r="3" stroke-width="2"></circle>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 16l5-5-5-5M8 8L3 13l5 5"></path>
        </svg>
    @break

    @default
        <span class="sr-only">icon {{ $name }}</span>
@endswitch

