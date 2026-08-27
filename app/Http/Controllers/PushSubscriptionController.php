<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'key' => ['required', 'string'],
            'token' => ['required', 'string'],
            'encoding' => ['nullable', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['key'],
            $validated['token'],
            $validated['encoding'] ?? null,
        );

        return response()->json([
            'message' => 'Subscription push berhasil disimpan.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->json([
            'message' => 'Subscription push berhasil dihapus.',
        ]);
    }
}
