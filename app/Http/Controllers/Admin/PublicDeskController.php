<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PublicHotline;
use App\Models\SystemSetting;
use App\Support\AlertPosture;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicDeskController extends Controller
{
    public function index(): View
    {
        return view('admin.public_desk.index', [
            'settings' => SystemSetting::current(),
            'postures' => AlertPosture::options(),
            'hotlines' => PublicHotline::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function updatePosture(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alert_level' => ['required', Rule::in(array_keys(AlertPosture::options()))],
            'alert_note' => ['nullable', 'string', 'max:280'],
        ]);

        SystemSetting::current()->update([
            'alert_level' => $data['alert_level'],
            'alert_note' => $data['alert_note'] ?: null,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Public alert posture updated.');
    }

    public function storeHotline(Request $request): RedirectResponse
    {
        PublicHotline::create($this->hotlineData($request));

        return back()->with('success', 'Hotline added to the public portal.');
    }

    public function updateHotline(Request $request, PublicHotline $hotline): RedirectResponse
    {
        $hotline->update($this->hotlineData($request));

        return back()->with('success', 'Hotline updated.');
    }

    public function destroyHotline(PublicHotline $hotline): RedirectResponse
    {
        $hotline->delete();

        return back()->with('success', 'Hotline removed.');
    }

    private function hotlineData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'number' => ['required', 'string', 'max:40'],
            'detail' => ['nullable', 'string', 'max:160'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $data['detail'] = $data['detail'] ?: null;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
