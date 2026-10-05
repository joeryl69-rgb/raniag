<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\EvacuationCenter;
use App\Models\IncidentType;
use App\Models\PublicHotline;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::published()
            ->latest('published_at')
            ->take(3)
            ->get();

        $incidentTypes = IncidentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(8)
            ->get(['name', 'icon', 'color', 'description']);

        $hotlines = PublicHotline::published()->take(4)->get();
        $openCenters = EvacuationCenter::query()->where('is_open', true)->count();

        return view('public.home', compact('announcements', 'incidentTypes', 'hotlines', 'openCenters'));
    }
}
