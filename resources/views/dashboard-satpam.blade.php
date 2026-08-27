@extends('layouts.app')

@section('title', 'Dashboard - Safety Patrol K3LH')
@section('page_title', 'Dashboard')

@php
    $statusBadgeClass = function (string $status): string {
        return match ($status) {
            'Selesai' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'Dalam Proses' => 'border-blue-200 bg-blue-50 text-blue-700',
            'Menunggu Tindakan' => 'border-yellow-200 bg-yellow-50 text-yellow-800',
            default => 'border-gray-200 bg-gray-50 text-gray-700',
        };
    };
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Dashboard Satpam</h2>
                <p class="text-sm text-gray-500 mt-1">Ringkasan laporan insiden darurat Anda</p>
            </div>
            <a href="{{ route('laporan-insiden') }}"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-red-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-red-700 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Buat Laporan Insiden
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-500 font-medium">Laporan Bulan Ini</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $summary['laporan_bulan_ini'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">Insiden yang Anda laporkan</p>
                        </div>
                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-500 font-medium">Menunggu Tindakan</p>
                            <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $summary['menunggu'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">Belum ditindaklanjuti K3LH</p>
                        </div>
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-500 font-medium">Dalam Proses</p>
                            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $summary['dalam_proses'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">Sedang ditangani</p>
                        </div>
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-500 font-medium">Selesai</p>
                            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ $summary['selesai'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">Sudah ditutup</p>
                        </div>
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        {{-- Recent reports --}}
        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-header class="pb-3 pt-6 px-6">
                <x-ui.card-title>Laporan insiden terbaru</x-ui.card-title>
                <p class="text-xs text-gray-500 mt-1">8 laporan terakhir yang Anda kirim.</p>
            </x-ui.card-header>
            <x-ui.card-content class="px-6 pb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">No. Laporan</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Jenis Insiden</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Lokasi</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($laporanTerbaru as $row)
                                <tr class="border-b border-gray-50">
                                    <td class="py-3 px-3 text-gray-900 font-medium whitespace-nowrap">{{ $row['nomor'] }}</td>
                                    <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $row['tanggal'] }}</td>
                                    <td class="py-3 px-3 text-gray-900">{{ $row['jenis'] }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $row['lokasi'] }}</td>
                                    <td class="py-3 px-3">
                                        <x-ui.badge variant="outline" class="{{ $statusBadgeClass($row['status']) }}">
                                            {{ $row['status'] }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 px-3 text-center text-sm text-gray-500">
                                        Belum ada laporan insiden.
                                        <a href="{{ route('laporan-insiden') }}" class="text-blue-600 font-medium hover:underline">Buat laporan pertama</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
