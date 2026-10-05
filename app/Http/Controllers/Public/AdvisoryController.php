<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\EvacuationCenter;
use App\Models\PublicHotline;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvisoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $announcements = Announcement::published()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhere('badge', 'like', "%{$search}%");
                });
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.advisories.index', [
            'announcements' => $announcements,
            'hotlines' => PublicHotline::published()->get(),
            'search' => $search,
            'openCenters' => EvacuationCenter::query()->where('is_open', true)->count(),
        ]);
    }
}
