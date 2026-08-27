<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Support\NotifikasiLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Notifikasi::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $aparKodes = NotifikasiLink::aparKodesByReference($items);

        $data = $items->map(function (Notifikasi $notifikasi) use ($aparKodes) {
            $aparKode = $notifikasi->reference_id
                ? ($aparKodes[$notifikasi->reference_id] ?? null)
                : null;

            return [
                'id' => $notifikasi->id,
                'jenis_notifikasi' => $notifikasi->jenis_notifikasi,
                'judul' => $notifikasi->judul,
                'pesan' => $notifikasi->pesan,
                'reference_id' => $notifikasi->reference_id,
                'is_read' => $notifikasi->is_read,
                'created_at' => $notifikasi->created_at?->toIso8601String(),
                'url' => NotifikasiLink::for($notifikasi, $aparKode),
            ];
        });

        $unreadCount = Notifikasi::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'data' => $data,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403);

        if (! $notifikasi->is_read) {
            $notifikasi->update(['is_read' => true]);
        }

        $unreadCount = Notifikasi::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'message' => 'Notifikasi ditandai dibaca.',
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notifikasi::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai dibaca.',
            'unread_count' => 0,
        ]);
    }
}
