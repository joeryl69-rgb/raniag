<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\DashboardAlertFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardAlertFeedController extends Controller
{
    public function __invoke(Request $request, DashboardAlertFeed $alerts): JsonResponse
    {
        $data = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()
            ->json($alerts->after($request->user(), (int) ($data['after'] ?? 0)))
            ->header('Cache-Control', 'no-store, private');
    }
}
