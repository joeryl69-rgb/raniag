<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function toggleSelf(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $user->is_available = ! (bool) $user->is_available;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json(['is_available' => $user->is_available]);
        }

        return back()->with('success', $user->is_available ? 'You are available.' : 'You are marked unavailable.');
    }

    public function pingLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'last_lat' => $data['lat'],
            'last_lng' => $data['lng'],
            'last_location_at' => now(),
        ])->save();

        return response()->json(['ok' => true]);
    }
}
