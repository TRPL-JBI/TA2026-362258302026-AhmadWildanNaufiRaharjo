<?php

namespace App\Support;

use App\Models\User;

class NavigationMenu
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function for(User $user): array
    {
        $dashboard = [
            'label' => 'Dashboard',
            'href' => route('dashboard', [], false),
            'icon' => 'bar-chart',
        ];

        return match ($user->role) {
            'Petugas K3LH' => [
                $dashboard,
                self::patroli(),
                self::inventarisPetugas(),
                ['label' => 'Pemantauan IPAM', 'href' => route('pemantauan.ipam', [], false), 'icon' => 'droplets'],
                ['label' => 'Pemantauan IPAL', 'href' => route('pemantauan.ipal', [], false), 'icon' => 'flask'],
                ['label' => 'Pemantauan B3', 'href' => route('pemantauan.b3', [], false), 'icon' => 'beaker'],
                ['label' => 'Tindak Lanjut', 'href' => route('tindak-lanjut', [], false), 'icon' => 'alert-triangle'],
                ['label' => 'Pedoman SOP', 'href' => route('sop', [], false), 'icon' => 'clipboard-list', 'activeMatch' => 'sop'],
                ['label' => 'Laporan', 'href' => route('laporan', [], false), 'icon' => 'file-text'],
            ],
            'Kalab' => [
                $dashboard,
                self::inventarisKalab(),
                ['label' => 'Pemantauan B3', 'href' => route('pemantauan.b3', [], false), 'icon' => 'beaker'],
                ['label' => 'Pedoman SOP', 'href' => route('sop', [], false), 'icon' => 'clipboard-list', 'activeMatch' => 'sop'],
            ],
            'Satpam' => [
                $dashboard,
                ['label' => 'Laporan Insiden', 'href' => route('laporan-insiden', [], false), 'icon' => 'alert-triangle'],
                ['label' => 'Pedoman SOP', 'href' => route('sop', [], false), 'icon' => 'clipboard-list', 'activeMatch' => 'sop'],
            ],
            'Pimpinan' => [
                $dashboard,
                ['label' => 'Laporan', 'href' => route('laporan', [], false), 'icon' => 'file-text'],
            ],
            default => [$dashboard],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function patroli(): array
    {
        return [
            'label' => 'Patroli',
            'href' => route('patroli.riwayat', [], false),
            'icon' => 'qr-code',
            'activeMatch' => 'patroli',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function inventarisPetugas(): array
    {
        return [
            'label' => 'Inventaris',
            'href' => '/inventaris',
            'icon' => 'map-pin',
            'activeMatch' => 'inventaris',
            'children' => [
                ['label' => 'Kelola Lokasi', 'href' => route('inventaris.lokasi', [], false)],
                ['label' => 'Kelola APAR', 'href' => route('inventaris.apar', [], false)],
                ['label' => 'Kelola Titik IPAM', 'href' => route('inventaris.ipam', [], false)],
                ['label' => 'Kelola Checklist Temuan', 'href' => route('inventaris.checklist-temuan', [], false)],
                ['label' => 'Manajemen User', 'href' => route('inventaris.user', [], false)],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function inventarisKalab(): array
    {
        return [
            'label' => 'Inventaris',
            'href' => route('inventaris.checklist-temuan', [], false),
            'icon' => 'map-pin',
            'activeMatch' => 'inventaris',
            'children' => [
                ['label' => 'Kelola Checklist Temuan', 'href' => route('inventaris.checklist-temuan', [], false)],
            ],
        ];
    }

    /**
     * @return array{name: string, roleLabel: string}
     */
    public static function userInfo(User $user): array
    {
        return [
            'name' => $user->nama_lengkap,
            'roleLabel' => $user->role,
        ];
    }
}
