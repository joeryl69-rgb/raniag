<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with('author')->latest('published_at')->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        Announcement::create($data);

        return back()->with('success', 'Announcement published to the landing page.');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        return back()->with('success', 'Announcement updated.');
    }

    public function toggle(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_published' => ! $announcement->is_published]);

        return back()->with('success', $announcement->is_published ? 'Announcement published.' : 'Announcement unpublished.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'badge' => ['nullable', 'string', 'max:40'],
            'body' => ['required', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^bi-[a-z0-9\-]+$/'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);
    }
}
