<!DOCTYPE html>
<html lang="id" class="h-full">

@php
    $navUser = auth()->user();
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Sistem Informasi Keselamatan, Kesehatan Kerja, dan Lingkungan Hidup')">
    <title>@yield('title', 'Safety Patrol K3LH - Politeknik Negeri Banyuwangi')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        @if ($navUser?->hasRole('Petugas K3LH') && filled(config('webpush.vapid.public_key')))
            <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
        @endif
    @endauth
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="@yield('body_class', 'font-sans antialiased h-full overflow-hidden')">

    <x-app-navbar
        :menu-items="$navUser ? \App\Support\NavigationMenu::for($navUser) : []"
        :user-info="$navUser ? \App\Support\NavigationMenu::userInfo($navUser) : ['name' => 'User', 'roleLabel' => '']"
        :page-title="trim($__env->yieldContent('page_title')) ?: 'Dashboard'">
        @hasSection('content')
            @yield('content')
        @elseif (isset($slot))
            {{ $slot }}
        @endif
    </x-app-navbar>

    @auth
        @if ($navUser?->hasRole('Petugas K3LH') && $navUser->canAccessRoute('push.subscribe') && filled(config('webpush.vapid.public_key')))
            <script id="webpush-config" type="application/json">@json([
                'enabled' => true,
                'subscribeUrl' => route('push.subscribe'),
            ])</script>
        @endif
    @endauth

    @include('partials.prevent-bfcache')

    <x-ui.dialog-host />
</body>

</html>
