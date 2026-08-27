@extends('layouts.guest')

@section('title', 'Login - Safety Patrol K3LH')
@section('body_class', 'font-sans antialiased min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-emerald-50 p-4')

@section('content')
    <x-ui.card class="w-full max-w-md shadow-xl border-0">
        <x-ui.card-content class="p-8"
            x-data="{ showPassword: false, loading: false }">
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 text-balance text-center">
                    Safety Patrol K3LH
                </h1>
                <p class="text-sm text-gray-500 mt-1 text-center">
                    Sistem Informasi Keselamatan dan Kesehatan Kerja
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    Politeknik Negeri Banyuwangi
                </p>
            </div>

            <form id="login-form" method="post" action="{{ route('login') }}" class="space-y-5" @submit="loading = true">
                @csrf

                @if (session('status'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-2">
                    <x-ui.label for="username" class="text-gray-700">Username</x-ui.label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path>
                        </svg>
                        <x-ui.input id="username" name="username" type="text" placeholder="Masukkan username"
                            value="{{ old('username') }}"
                            required
                            autocomplete="username"
                            class="h-11 pl-10 bg-gray-50 focus:bg-white @error('username') ring-2 ring-red-500 @enderror" />
                    </div>
                </div>

                <div class="space-y-2">
                    <x-ui.label for="password" class="text-gray-700">Password</x-ui.label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V7a4 4 0 0 0-8 0v4"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 11h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2z"></path>
                        </svg>
                        <x-ui.input id="password" name="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                            class="h-11 pl-10 pr-10 bg-gray-50 focus:bg-white @error('password') ring-2 ring-red-500 @enderror" />
                        <button type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            x-on:click="showPassword = !showPassword"
                            x-bind:aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                            <svg x-show="!showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.58 10.58A3 3 0 0 0 12 15a3 3 0 0 0 2.12-.88"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 4.24A10.94 10.94 0 0 1 12 4c7 0 10 8 10 8a17.47 17.47 0 0 1-3.14 4.5"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.11 6.11C3.73 8.04 2 12 2 12s3 8 10 8a10.6 10.6 0 0 0 4.3-.9"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ old('remember') ? 'checked' : '' }}>
                    Ingat saya
                </label>

                <x-ui.button type="submit" class="w-full h-11" x-bind:disabled="loading">
                    <span x-text="loading ? 'Memproses...' : 'Login'"></span>
                </x-ui.button>
            </form>
        </x-ui.card-content>
    </x-ui.card>

    @auth
        <script>window.location.replace(@json(route('dashboard')));</script>
    @endauth
@endsection
