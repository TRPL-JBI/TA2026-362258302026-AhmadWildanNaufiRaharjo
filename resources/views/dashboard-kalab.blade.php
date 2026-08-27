@extends('layouts.app')

@section('title', 'Dashboard - Safety Patrol K3LH')
@section('page_title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Dashboard Kalab</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $lokasiNama }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <p class="text-sm text-gray-500 font-medium">Checklist Aktif</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $summary['checklist_aktif'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Checklist temuan bahaya laboratorium Anda</p>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <p class="text-sm text-gray-500 font-medium">Dokumen SOP Tersedia</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-1">{{ $summary['dokumen_sop'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Pedoman operasional global</p>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Shortcut fitur</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('inventaris.checklist-temuan') }}" class="group block">
                    <x-ui.card class="border-0 shadow-sm h-full transition-shadow group-hover:shadow-md">
                        <x-ui.card-content class="p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-600">Checklist Temuan</p>
                                    <p class="text-xs text-gray-500 mt-1">Kelola checklist temuan bahaya laboratorium Anda.</p>
                                </div>
                                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center shrink-0">
                                    <x-icon name="check-square" class="w-4 h-4 text-indigo-600" />
                                </div>
                            </div>
                        </x-ui.card-content>
                    </x-ui.card>
                </a>

                <a href="{{ route('pemantauan.b3') }}" class="group block">
                    <x-ui.card class="border-0 shadow-sm h-full transition-shadow group-hover:shadow-md">
                        <x-ui.card-content class="p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-600">Pemantauan B3</p>
                                    <p class="text-xs text-gray-500 mt-1">Pantau laporan limbah B3 dan status pengangkutan.</p>
                                </div>
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                                    <x-icon name="beaker" class="w-4 h-4 text-amber-600" />
                                </div>
                            </div>
                        </x-ui.card-content>
                    </x-ui.card>
                </a>

                <a href="{{ route('sop') }}" class="group block">
                    <x-ui.card class="border-0 shadow-sm h-full transition-shadow group-hover:shadow-md">
                        <x-ui.card-content class="p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-600">Pedoman SOP</p>
                                    <p class="text-xs text-gray-500 mt-1">Lihat dokumen standar operasional prosedur.</p>
                                </div>
                                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                                    <x-icon name="file-text" class="w-4 h-4 text-emerald-600" />
                                </div>
                            </div>
                        </x-ui.card-content>
                    </x-ui.card>
                </a>
            </div>
        </div>

        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-header class="pb-3 pt-6 px-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <x-ui.card-title>Pedoman SOP terbaru</x-ui.card-title>
                        <p class="text-xs text-gray-500 mt-1">Dokumen pedoman yang dapat Anda preview.</p>
                    </div>
                    <a href="{{ route('sop') }}" class="text-sm font-medium text-blue-600 hover:underline shrink-0">
                        Lihat semua
                    </a>
                </div>
            </x-ui.card-header>
            <x-ui.card-content class="px-6 pb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Judul</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">File</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Diperbarui</th>
                                <th class="text-right py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dokumenSopTerbaru as $row)
                                <tr class="border-b border-gray-50">
                                    <td class="py-3 px-3 text-gray-900 font-medium">{{ $row['judul'] }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $row['original_filename'] }}</td>
                                    <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $row['updated_at'] }}</td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="{{ $row['preview_url'] }}" target="_blank" class="text-sm font-medium text-blue-600 hover:underline">
                                            Preview
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 px-3 text-center text-sm text-gray-500">
                                        Belum ada dokumen SOP.
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
