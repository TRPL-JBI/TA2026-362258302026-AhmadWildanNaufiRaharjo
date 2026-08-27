@extends('layouts.app')

@section('title', 'Dashboard Eksekutif - Safety Patrol K3LH')
@section('page_title', 'Dashboard Eksekutif')

@php
    $riskBadgeClass = function (string $level): string {
        return match ($level) {
            'Sangat Tinggi' => 'border-red-200 bg-red-50 text-red-700',
            'Tinggi' => 'border-orange-200 bg-orange-50 text-orange-700',
            'Sedang' => 'border-amber-200 bg-amber-50 text-amber-800',
            default => 'border-gray-200 bg-gray-50 text-gray-700',
        };
    };

    $statusBadgeClass = function (string $status): string {
        return match ($status) {
            'Selesai' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'Dalam Proses' => 'border-blue-200 bg-blue-50 text-blue-700',
            'Menunggu Tindakan' => 'border-yellow-200 bg-yellow-50 text-yellow-800',
            default => 'border-gray-200 bg-gray-50 text-gray-700',
        };
    };

    $timelineDot = function (string $status): string {
        return match ($status) {
            'Selesai' => 'bg-emerald-500',
            'Dalam Proses' => 'bg-blue-500',
            default => 'bg-yellow-500',
        };
    };

@endphp

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Dashboard Eksekutif</h2>
            <p class="text-sm text-gray-500 mt-1">Monitoring real-time K3LH Politeknik Negeri Banyuwangi</p>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-500 font-medium">Total Temuan Bulan Ini</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $summary['temuan_bulan_ini'] }}</p>
                            @if ($summary['persen_perubahan_temuan'] !== null)
                                @php
                                    $naik = $summary['persen_perubahan_temuan'] >= 0;
                                    $warnaTren = $naik ? 'text-emerald-600' : 'text-red-600';
                                @endphp
                                <p class="text-xs {{ $warnaTren }} mt-1 flex items-center gap-1">
                                    @if ($naik)
                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-8"/></svg>
                                    @else
                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l6 6 4-4 8 8"/></svg>
                                    @endif
                                    {{ abs($summary['persen_perubahan_temuan']) }}% dari bulan lalu
                                </p>
                            @else
                                <p class="text-xs text-gray-500 mt-1">Belum ada data bulan lalu</p>
                            @endif
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
                            <p class="text-sm text-gray-500 font-medium">Tindak Lanjut Selesai</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $summary['tindak_lanjut_selesai'] }} / {{ $summary['tindak_lanjut_total'] }}</p>
                            <x-ui.progress :value="$summary['tindak_lanjut_persen']" class="mt-2 h-2" />
                            <p class="text-xs text-gray-500 mt-1">{{ $summary['tindak_lanjut_belum_selesai'] }} masih dalam proses</p>
                        </div>
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-content class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-500 font-medium">APAR Mendekati Expired</p>
                            <p class="text-3xl font-bold text-red-600 mt-1">{{ $summary['apar_mendekati_expired'] }} Unit</p>
                            <p class="text-xs text-red-500 mt-1">Sudah expired atau &lt; 30 hari lagi</p>
                        </div>
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-header class="pb-2 pt-6 px-6">
                    <x-ui.card-title>Proporsi risiko K3LH rata-rata per lokasi</x-ui.card-title>
                    <p class="text-xs text-gray-500 mt-1">
                        @if ($risikoMeta['has_more'])
                            Top 5 dari {{ $risikoMeta['total_lokasi'] }} lokasi.
                        @elseif ($risikoMeta['total_lokasi'] > 0)
                            Skor rata-rata risiko temuan patroli per lokasi.
                        @endif
                    </p>
                </x-ui.card-header>
                <x-ui.card-content class="px-6 pb-6">
                    @if (count($risikoPerLokasi) === 0)
                        <p class="text-sm text-gray-500 py-8 text-center">Belum ada data temuan patroli untuk grafik risiko per lokasi.</p>
                    @else
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 sm:gap-8">
                        @php
                            $gradientParts = [];
                            $start = 0;
                            foreach ($risikoPerLokasi as $i => $row) {
                                $pct = $totalSkorLokasi > 0 ? ($row['skor'] / $totalSkorLokasi) * 100 : 0;
                                $end = $start + $pct;
                                $c = $warnaDonat[$i % count($warnaDonat)];
                                $gradientParts[] = "{$c} {$start}% {$end}%";
                                $start = $end;
                            }
                            $donutGradient = 'conic-gradient(' . implode(', ', $gradientParts) . ')';
                        @endphp
                        <div class="relative w-44 h-44 shrink-0 rounded-full" style="background: {{ $donutGradient }}">
                            <div class="absolute inset-[22%] rounded-full bg-white flex items-center justify-center shadow-inner">
                                <div class="text-center px-2">
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Total skor</p>
                                    <p class="text-lg font-bold text-gray-900">{{ $totalSkorLokasi }}</p>
                                </div>
                            </div>
                        </div>
                        <ul class="flex-1 w-full min-w-0 max-h-52 overflow-y-auto space-y-2 text-sm pr-1">
                            @foreach ($risikoPerLokasi as $i => $row)
                                @php
                                    $pct = $totalSkorLokasi > 0 ? round(($row['skor'] / $totalSkorLokasi) * 100) : 0;
                                    $isLainnya = ! empty($row['is_lainnya']);
                                @endphp
                                <li class="flex items-center justify-between gap-3 {{ $isLainnya ? 'pt-1 border-t border-gray-100' : '' }}">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $warnaDonat[$i % count($warnaDonat)] }}"></span>
                                        <span class="{{ $isLainnya ? 'text-gray-600 italic' : 'text-gray-900 font-medium' }} truncate">{{ $row['lokasi'] }}</span>
                                    </span>
                                    <span class="text-gray-600 shrink-0 tabular-nums">{{ $pct }}% <span class="text-gray-400">({{ $row['skor'] }})</span></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-header class="pb-2 pt-6 px-6">
                    <x-ui.card-title>Tren temuan per caturwulan</x-ui.card-title>
                    <p class="text-xs text-gray-500 mt-1">
                        Pemantauan 3 kali setahun (agregasi per 4 bulan)
                        @if (count($trenPerEmpatBulan) > 0)
                            · {{ $trenPerEmpatBulan[0]['tahun'] }}
                        @endif
                    </p>
                </x-ui.card-header>
                <x-ui.card-content class="px-6 pb-6">
                    <div class="space-y-3">
                    <div class="h-52 w-full">
                        <svg viewBox="0 0 400 190" class="w-full h-full" role="img" aria-label="Grafik tren temuan per caturwulan">
                            <defs>
                                <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.25"/>
                                    <stop offset="100%" stop-color="#3B82F6" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            @php
                                $n = count($trenPerEmpatBulan);
                                $pad = 36;
                                $w = 400;
                                $h = 190;
                                $plotW = $w - $pad * 2;
                                $plotH = $h - $pad * 2;
                                $points = [];
                                foreach ($trenPerEmpatBulan as $idx => $row) {
                                    $x = $pad + ($n === 1 ? 0 : ($idx / ($n - 1)) * $plotW);
                                    $y = $pad + $plotH - ($row['temuan'] / $maxTemuan) * $plotH;
                                    $points[] = ['x' => $x, 'y' => $y, 'val' => $row['temuan']];
                                }
                                $pathLine = collect($points)->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L') . round($p['x'], 1) . ' ' . round($p['y'], 1))->implode(' ');
                                $pathArea = $pathLine
                                    . ' L' . round($points[$n - 1]['x'], 1) . ' ' . round($pad + $plotH, 1)
                                    . ' L' . round($points[0]['x'], 1) . ' ' . round($pad + $plotH, 1)
                                    . ' Z';
                            @endphp
                            <rect x="0" y="0" width="{{ $w }}" height="{{ $h }}" fill="white"/>
                            <g stroke="#f3f4f6">
                                @for ($gy = 0; $gy <= 4; $gy++)
                                    @php $yy = $pad + ($gy / 4) * $plotH; @endphp
                                    <line x1="{{ $pad }}" y1="{{ $yy }}" x2="{{ $w - $pad }}" y2="{{ $yy }}"/>
                                @endfor
                            </g>
                            <path d="{{ $pathArea }}" fill="url(#areaFill)" />
                            <path d="{{ $pathLine }}" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                            @foreach ($points as $p)
                                <circle cx="{{ round($p['x'], 1) }}" cy="{{ round($p['y'], 1) }}" r="4" fill="white" stroke="#3B82F6" stroke-width="2"/>
                                <text x="{{ round($p['x'], 1) }}" y="{{ round($p['y'], 1) - 10 }}" text-anchor="middle" class="fill-gray-700 font-semibold" style="font-size: 11px">{{ $p['val'] }}</text>
                            @endforeach
                        </svg>
                    </div>
                    <div class="grid grid-cols-3 gap-2 px-1 sm:px-4 text-center">
                        @foreach ($trenPerEmpatBulan as $row)
                            <div class="min-w-0 px-1">
                                <p class="text-xs sm:text-sm font-medium text-gray-800 leading-tight">{{ $row['periode'] }}</p>
                                <p class="text-[10px] sm:text-xs text-gray-400 mt-0.5">{{ $row['rentang_bulan'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        {{-- Priority table --}}
        <x-ui.card class="border-0 shadow-sm">
            <x-ui.card-header class="pb-3 pt-6 px-6">
                <x-ui.card-title>Temuan prioritas tinggi - memerlukan tindakan segera</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content class="px-6 pb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">No</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Lokasi</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Deskripsi</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Risiko</th>
                                <th class="text-left py-3 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prioritasTemuan as $i => $t)
                                <tr class="border-b border-gray-50 {{ $t['level'] === 'Sangat Tinggi' ? 'bg-red-50/50' : 'bg-orange-50/30' }}">
                                    <td class="py-3 px-3 text-gray-600">{{ $i + 1 }}</td>
                                    <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $t['tanggal'] }}</td>
                                    <td class="py-3 px-3 text-gray-900 font-medium">{{ $t['lokasi'] }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $t['kategori'] }}</td>
                                    <td class="py-3 px-3 text-gray-600 max-w-xs truncate">{{ $t['deskripsi'] }}</td>
                                    <td class="py-3 px-3">
                                        <x-ui.badge variant="outline" class="{{ $riskBadgeClass($t['level']) }}">
                                            {{ $t['level'] }} ({{ $t['skor'] }})
                                        </x-ui.badge>
                                    </td>
                                    <td class="py-3 px-3">
                                        <x-ui.badge variant="outline" class="{{ $statusBadgeClass($t['status']) }}">
                                            {{ $t['status'] }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 px-3 text-center text-sm text-gray-500">
                                        Tidak ada temuan prioritas tinggi saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui.card class="border-0 shadow-sm">
                <x-ui.card-header class="pb-3 pt-6 px-6">
                    <x-ui.card-title>Update tindak lanjut terbaru</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content class="px-6 pb-6">
                    <div class="space-y-4">
                        @forelse ($timeline as $i => $item)
                            <div class="flex gap-3">
                                <div class="w-2.5 h-2.5 rounded-full mt-1.5 shrink-0 {{ $timelineDot($item['status']) }}"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-400">{{ $item['date'] }}</p>
                                    <p class="text-sm text-gray-900 font-medium mt-0.5">
                                        <x-ui.badge variant="outline" class="text-[10px] mr-2 {{ $statusBadgeClass($item['status']) }}">{{ $item['status'] }}</x-ui.badge>
                                        {{ $item['desc'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">PJ: {{ $item['pelaksana'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 text-center py-6">Belum ada update tindak lanjut.</p>
                        @endforelse
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>
@endsection
