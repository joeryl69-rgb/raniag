<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\ThemePresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'setting' => SystemSetting::current(),
            'presets' => ThemePresets::PRESETS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme_key' => ['required', 'string', Rule::in(array_keys(ThemePresets::PRESETS))],
            'dark_mode' => ['sometimes', 'boolean'],
        ]);

        $setting = SystemSetting::current();
        $setting->update([
            'theme_key' => $data['theme_key'],
            'dark_mode' => $request->boolean('dark_mode'),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Theme updated. It now applies across every account for the whole system.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $setting = SystemSetting::current();
        $setting->update([
            'theme_key' => ThemePresets::DEFAULT_KEY,
            'dark_mode' => false,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Theme reset to the default (Ocean Blue, light mode).');
    }
}
