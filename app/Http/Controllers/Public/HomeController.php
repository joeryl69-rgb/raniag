<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::published()
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.home', compact('announcements'));
    }
}
