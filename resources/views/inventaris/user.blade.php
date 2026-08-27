@extends('layouts.app')

@section('title', 'Manajemen User - Safety Patrol K3LH')
@section('page_title', 'Manajemen User')

@php
    use Illuminate\Support\Js;

    $userPageConfig = [
        'showForm' => $errors->any(),
        'formUsername' => old('username', ''),
        'formPassword' => old('password', ''),
        'formNamaLengkap' => old('nama_lengkap', ''),
        'formRole' => old('role', 'Petugas K3LH'),
        'formLokasiId' => old('lokasi_id', ''),
        'formIsActive' => old('is_active', '1') === '1' || old('is_active') === true || old('is_active') === 1,
        'storeUrl' => route('inventaris.user.store'),
        'baseUrl' => url('/inventaris/user'),
        'roles' => $roles,
        'laboratorium' => $laboratorium->map(fn ($row) => $row->only(['id', 'nama_lokasi']))->values()->all(),
        'items' => $users->getCollection()->map(
            fn ($row) => [
                'id' => $row->id,
                'username' => $row->username,
                'nama_lengkap' => $row->nama_lengkap,
                'role' => $row->role,
                'lokasi_id' => $row->lokasi_id,
                'lokasi_nama' => $row->lokasi?->nama_lokasi,
                'is_active' => $row->is_active,
            ],
        )->values()->all(),
    ];
@endphp

@section('content')
    <div class="space-y-6 max-w-[100rem] mx-auto" x-data="inventarisUser({{ Js::from($userPageConfig) }})">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <a href="{{ route('dashboard') }}" class="shrink-0 mt-0.5">
                    <x-ui.button variant="ghost" size="icon" aria-label="Kembali">
                        <x-icon name="arrow-left" class="w-5 h-5" />
                    </x-ui.button>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Manajemen User</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Kelola akun pengguna sistem (username, role, status aktif).
                    </p>
                </div>
            </div>
            <x-ui.button class="bg-blue-600 hover:bg-blue-700 text-white shrink-0" type="button" x-on:click="openCreate()">
                <x-icon name="plus" class="w-4 h-4" />
                Tambah User
            </x-ui.button>
        </div>

        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-show="showForm" x-cloak
            x-on:keydown.escape.window="closeForm()">
            <div class="fixed inset-0 bg-black/40" x-on:click="closeForm()"></div>
            <div class="relative bg-white w-full sm:max-w-lg sm:rounded-xl rounded-t-xl border border-gray-200 shadow-xl max-h-[92vh] flex flex-col">
                <div class="p-5 border-b border-gray-100 flex items-start justify-between gap-3 shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Edit User' : 'Tambah User'"></h2>
                        <p class="text-xs text-gray-500 mt-0.5" x-show="editing">
                            Kosongkan password jika tidak ingin mengubahnya.
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600 rounded-lg p-1 hover:bg-gray-100"
                        x-on:click="closeForm()" aria-label="Tutup">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <form method="POST" x-bind:action="formAction()" class="flex flex-col min-h-0 flex-1">
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="p-5 space-y-4 overflow-y-auto">
                        <div class="space-y-1.5">
                            <x-ui.label class="text-xs font-semibold text-gray-700">
                                Username <span class="text-red-500">*</span>
                            </x-ui.label>
                            <x-ui.input name="username" type="text" class="h-10 bg-white" placeholder="contoh: budi.k3lh"
                                maxlength="50" required x-model="formUsername" />
                            @error('username')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <x-ui.label class="text-xs font-semibold text-gray-700">
                                <span x-text="passwordRequired() ? 'Password *' : 'Password baru'"></span>
                            </x-ui.label>
                            <x-ui.input name="password" type="password" class="h-10 bg-white"
                                x-bind:placeholder="passwordRequired() ? 'Minimal 8 karakter' : 'Kosongkan jika tidak diubah'"
                                x-bind:required="passwordRequired()" autocomplete="new-password" x-model="formPassword" />
                            @error('password')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <x-ui.label class="text-xs font-semibold text-gray-700">
                                Nama lengkap <span class="text-red-500">*</span>
                            </x-ui.label>
                            <x-ui.input name="nama_lengkap" type="text" class="h-10 bg-white" maxlength="100" required
                                x-model="formNamaLengkap" />
                            @error('nama_lengkap')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <x-ui.label class="text-xs font-semibold text-gray-700">
                                Role <span class="text-red-500">*</span>
                            </x-ui.label>
                            <select name="role" required x-model="formRole"
                                class="flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5" x-show="showLokasiField()" x-cloak>
                            <x-ui.label class="text-xs font-semibold text-gray-700">
                                Lab (Kalab) <span class="text-red-500">*</span>
                            </x-ui.label>
                            <select name="lokasi_id" x-model.number="formLokasiId" x-bind:required="showLokasiField()"
                                class="flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                @forelse ($laboratorium as $lab)
                                    <option value="{{ $lab->id }}">{{ $lab->nama_lokasi }}</option>
                                @empty
                                    <option value="" disabled>Belum ada lokasi laboratorium</option>
                                @endforelse
                            </select>
                            @error('lokasi_id')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="iu-form-active" name="is_active" value="1" x-model="formIsActive"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            <label for="iu-form-active" class="text-sm text-gray-700">Akun aktif</label>
                        </div>
                    </div>

                    <div class="p-5 border-t border-gray-100 flex flex-col-reverse sm:flex-row gap-2 shrink-0">
                        <x-ui.button type="button" variant="outline" class="flex-1" x-on:click="closeForm()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white">Simpan</x-ui.button>
                    </div>
                </form>
            </div>
        </div>

        <div class="fixed inset-0 z-50" x-show="deleteId" x-cloak>
            <div class="fixed inset-0 bg-black/40" x-on:click="cancelDelete()"></div>
            <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus user?</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        User yang masih terhubung ke patroli atau laporan tidak dapat dihapus.
                    </p>
                    <form method="POST" class="mt-4 flex justify-end gap-2" x-bind:action="deleteAction()">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="outline" x-on:click="cancelDelete()">Batal</x-ui.button>
                        <x-ui.button type="submit" class="bg-red-600 text-white hover:bg-red-700">Hapus</x-ui.button>
                    </form>
                </div>
            </div>
        </div>

        <x-ui.card class="border border-gray-200 shadow-sm">
            <x-ui.card-header class="pb-3">
                <form method="GET" action="{{ route('inventaris.user') }}"
                    class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.card-title class="text-base">Daftar User</x-ui.card-title>
                    <x-ui.input name="q" value="{{ $search }}" placeholder="Cari username, nama, atau role..."
                        class="h-9 max-w-xs" />
                </form>
            </x-ui.card-header>

            <x-ui.card-content class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[800px]">
                        <thead>
                            <tr class="bg-gray-50 border-y border-gray-200">
                                <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">No</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Username</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Nama lengkap</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Role</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Lab / lokasi</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Status</th>
                                <th class="text-right px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $idx => $item)
                                <tr class="border-b border-gray-100 hover:bg-gray-50/80 align-top">
                                    <td class="px-4 py-3 text-gray-500 tabular-nums">
                                        {{ $users->firstItem() + $idx }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $item->username }}</td>
                                    <td class="px-4 py-3 text-gray-900">{{ $item->nama_lengkap }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span @class([
                                            'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                            $roleBadgeClass[$item->role] ?? 'bg-gray-50 text-gray-800 border-gray-200',
                                        ])>
                                            {{ $item->role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($item->role === 'Kalab')
                                            {{ $item->lokasi?->nama_lokasi ?? '-' }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($item->is_active)
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1 justify-end">
                                            <x-ui.button type="button" variant="ghost" size="icon"
                                                class="h-8 w-8 text-gray-500 hover:text-blue-600" aria-label="Edit"
                                                x-on:click="openEditById({{ $item->id }})">
                                                <x-icon name="pencil" class="w-4 h-4" />
                                            </x-ui.button>
                                            @if ($item->id !== auth()->id())
                                                <x-ui.button type="button" variant="ghost" size="icon"
                                                    class="h-8 w-8 text-gray-500 hover:text-red-600" aria-label="Hapus"
                                                    x-on:click="confirmDelete({{ $item->id }})">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 text-sm">
                                        Belum ada data user. Klik &quot;Tambah User&quot; untuk memulai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-ui.server-pagination :paginator="$users" />
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
