<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Sistem Informasi Keselamatan, Kesehatan Kerja, dan Lingkungan Hidup')">
    <title>@yield('title', 'Safety Patrol K3LH - Politeknik Negeri Banyuwangi')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (filled(config('webpush.vapid.public_key')))
        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    @endif
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="@yield('body_class', 'font-sans antialiased')">
    @yield('content')

    @include('partials.prevent-bfcache')
</body>

</html>

