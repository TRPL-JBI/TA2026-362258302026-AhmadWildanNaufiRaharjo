@props([
    // tampilan statis dulu
    'menuItems' => null,
    'userInfo' => null,
    'pageTitle' => null,
])

@php
    $menuItems = $menuItems ?? [];
    $userInfo = $userInfo ?? ['name' => 'User', 'roleLabel' => ''];
    $pageTitle = $pageTitle ?? 'Dashboard';

    $path = '/' . ltrim(request()->path(), '/');
    $isActive = function (string $href) use ($path): bool {
        $href = '/' . ltrim($href, '/');
        return $path === $href || str_starts_with($path, rtrim($href, '/') . '/');
    };

    /** Aktif menu item tanpa anak. */
    $itemIsActive = function (array $item) use ($path, $isActive): bool {
        if (!isset($item['activeMatch'])) {
            return $isActive($item['href'] ?? '');
        }

        return match ($item['activeMatch']) {
            'sop' => str_starts_with($path, '/sop'),
            'patroli' => str_starts_with($path, '/patroli'),
            'inventaris' => str_starts_with($path, '/inventaris'),
            default => $isActive($item['href'] ?? ''),
        };
    };

    /** Submenu anak aktif jika path cocok. sibling dengan href lebih panjang diutamakan. */
    $childIsActive = function (array $child, array $siblings) use ($path): bool {
        $href = '/' . ltrim($child['href'] ?? '', '/');

        if ($href === '/' || ($path !== $href && ! str_starts_with($path, rtrim($href, '/') . '/'))) {
            return false;
        }

        foreach ($siblings as $other) {
            $otherHref = '/' . ltrim($other['href'] ?? '', '/');

            if ($otherHref === $href || $otherHref === '/') {
                continue;
            }

            if (
                strlen($otherHref) > strlen($href)
                && ($path === $otherHref || str_starts_with($path, rtrim($otherHref, '/') . '/'))
            ) {
                return false;
            }
        }

        return true;
    };

    $openSubmenu = null;

    foreach ($menuItems as $item) {
        if (empty($item['children']) || ! is_array($item['children'])) {
            continue;
        }

        if ($itemIsActive($item)) {
            $openSubmenu = $item['label'];
            break;
        }
    }

@endphp

<div class="flex h-screen overflow-hidden bg-gray-50"
    x-data="{ sidebarOpen: false, openSubmenu: @js($openSubmenu) }">
    <div class="fixed inset-0 bg-black/40 z-40 lg:hidden" x-show="sidebarOpen" x-cloak
        x-on:click="sidebarOpen = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:static lg:z-auto lg:h-full"
        x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="h-16 flex items-center gap-3 px-5 border-b border-gray-100 shrink-0">
            <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center">
                <x-icon name="shield" class="w-5 h-5 text-white" />
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 leading-tight">Safety Patrol</p>
                <p class="text-[10px] text-gray-400 leading-tight">K3LH Poliwangi</p>
            </div>
            <button class="ml-auto lg:hidden text-gray-400" x-on:click="sidebarOpen = false" aria-label="Tutup menu">
                <x-icon name="x" class="w-5 h-5" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            @foreach ($menuItems as $item)
                @php
                    $hasChildren = isset($item['children']) && is_array($item['children']) && count($item['children']) > 0;
                    $active = $hasChildren && ! isset($item['activeMatch'])
                        ? $isActive($item['href'] ?? '')
                        : $itemIsActive($item);
                @endphp

                @if ($hasChildren)
                    <div>
                        <button type="button"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                            x-on:click="openSubmenu = (openSubmenu === '{{ addslashes($item['label']) }}') ? null : '{{ addslashes($item['label']) }}'">
                            <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 shrink-0" />
                            <span class="flex-1 text-left">{{ $item['label'] }}</span>
                            <x-icon name="chevron-down" class="w-4 h-4 transition-transform"
                            x-bind:class="openSubmenu === '{{ addslashes($item['label']) }}' ? 'rotate-180' : ''" />
                        </button>

                        <div class="ml-8 mt-1 space-y-0.5" x-show="openSubmenu === '{{ addslashes($item['label']) }}'" x-cloak>
                            @foreach ($item['children'] as $child)
                                @php $childActive = $childIsActive($child, $item['children']); @endphp
                                <a href="{{ $child['href'] }}"
                                    class="block px-3 py-2 rounded-md text-sm transition-colors {{ $childActive ? 'text-blue-700 bg-blue-50 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}"
                                    x-on:click="sidebarOpen = false">
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ $item['href'] }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                        x-on:click="sidebarOpen = false">
                        <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 shrink-0" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gray-200 rounded-full flex items-center justify-center">
                    <x-icon name="user" class="w-4 h-4 text-gray-600" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $userInfo['name'] }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $userInfo['roleLabel'] }}</p>
                </div>
            </div>

            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-3" id="logout-form-sidebar">
                    @csrf
                    <button type="submit"
                        class="w-full cursor-pointer inline-flex items-center gap-2 justify-start text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-md px-3 py-2 transition-colors">
                        <x-icon name="log-out" class="w-4 h-4" />
                        Logout
                    </button>
                </form>
            @endauth
        </div>
    </aside>

    <div class="flex min-h-0 min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4 lg:px-6">
            <button class="lg:hidden text-gray-600" x-on:click="sidebarOpen = true" aria-label="Buka menu">
                <x-icon name="menu" class="w-6 h-6" />
            </button>
            <div class="flex-1">
                <h1 class="text-sm font-semibold text-gray-900">{{ $pageTitle }}</h1>
            </div>

            @auth
                @if (auth()->user()->hasRole('Petugas K3LH'))
                <div class="relative"
                    x-data="notificationBell(@js([
                        'enabled' => true,
                        'indexUrl' => route('notifikasi.index'),
                        'readUrlTemplate' => route('notifikasi.read', ['notifikasi' => '__ID__']),
                        'readAllUrl' => route('notifikasi.read-all'),
                    ]))"
                    x-on:keydown.escape.window="open = false">
                    <button type="button"
                        class="relative text-gray-500 hover:text-gray-700 rounded-md p-1.5 hover:bg-gray-100 transition-colors"
                        aria-label="Notifikasi"
                        x-on:click="togglePanel()">
                        <x-icon name="bell" class="w-5 h-5" />
                        <span x-show="unreadCount > 0" x-cloak
                            class="absolute -top-1 -right-1 h-4 min-w-4 px-1 text-[10px] bg-red-500 text-white border-0 rounded-full flex items-center justify-center"
                            x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                    </button>

                    <div x-show="open" x-cloak x-on:click.outside="open = false"
                        class="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden">
                        <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900">Notifikasi</p>
                            <button type="button"
                                class="text-xs font-medium text-blue-600 hover:text-blue-700 disabled:text-gray-300"
                                x-bind:disabled="unreadCount === 0"
                                x-on:click="markAllRead()">
                                Tandai semua dibaca
                            </button>
                        </div>

                        <div class="max-h-80 overflow-y-auto">
                            <template x-if="loading && items.length === 0">
                                <p class="px-4 py-6 text-sm text-gray-500 text-center">Memuat notifikasi...</p>
                            </template>

                            <template x-if="!loading && items.length === 0">
                                <p class="px-4 py-6 text-sm text-gray-500 text-center">Belum ada notifikasi.</p>
                            </template>

                            <template x-for="item in items" :key="item.id">
                                <button type="button"
                                    class="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors"
                                    x-bind:class="item.is_read ? 'bg-white' : 'bg-blue-50/40'"
                                    x-on:click="openNotification(item)">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1.5 h-2 w-2 rounded-full shrink-0"
                                            x-bind:class="item.is_read ? 'bg-transparent' : 'bg-blue-500'"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="item.judul"></p>
                                            <p class="text-xs text-gray-600 mt-0.5 line-clamp-2" x-text="item.pesan"></p>
                                            <p class="text-[11px] text-gray-400 mt-1" x-text="item.relativeTime"></p>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                @endif
            @endauth

            <div class="hidden sm:flex items-center gap-3 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <x-icon name="user" class="w-4 h-4 text-blue-600" />
                    </div>
                    <span class="font-medium text-gray-800">{{ $userInfo['name'] }}</span>
                </div>

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="inline" id="logout-form-header">
                        @csrf
                        <button type="submit"
                            class="cursor-pointer inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-md px-2.5 py-1.5 transition-colors"
                            title="Logout">
                            <x-icon name="log-out" class="w-4 h-4" />
                            <span class="hidden md:inline">Logout</span>
                        </button>
                    </form>
                @endauth
            </div>
        </header>

        <main class="min-h-0 flex-1 overflow-y-auto p-4 lg:p-6">
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

