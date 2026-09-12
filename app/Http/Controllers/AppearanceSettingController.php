<?php

namespace App\Http\Controllers;

use App\Support\ThemePresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppearanceSettingController extends Controller
{
    /**
     * Every authenticated role gets this page from their own profile menu —
     * previously it was an admin-only screen whose choices applied to
     * everyone's account (see the appearance() accessor on App\Models\User
     * for why that's no longer the case).
     */
    public function index(): View
    {
        return view('settings.appearance', [
            'setting' => (object) $this->currentUserOrDefaults(),
            'presets' => ThemePresets::PRESETS,
            'fonts' => ThemePresets::FONTS,
            'fontSizes' => ThemePresets::FONT_SIZES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme_key' => ['required', 'string', Rule::in(array_keys(ThemePresets::PRESETS))],
            'dark_mode' => ['sometimes', 'boolean'],
            'follow_system' => ['sometimes', 'boolean'],
            'font_key' => ['required', 'string', Rule::in(array_keys(ThemePresets::FONTS))],
            'font_size' => ['required', 'string', Rule::in(array_keys(ThemePresets::FONT_SIZES))],
        ]);

        // Dark mode and "follow system" are mutually exclusive — if both
        // arrived checked (shouldn't happen with the disabled inputs on the
        // form, but a stale tab/replay could still post both), follow-system
        // wins since it's the more specific, ongoing choice.
        $followSystem = $request->boolean('follow_system');

        $request->user()->update([
            'theme_key' => $data['theme_key'],
            'dark_mode' => $followSystem ? false : $request->boolean('dark_mode'),
            'follow_system' => $followSystem,
            'font_key' => $data['font_key'],
            'font_size' => $data['font_size'],
        ]);

        return back()->with('success', 'Your appearance settings have been updated.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->user()->update([
            'theme_key' => ThemePresets::DEFAULT_KEY,
            'dark_mode' => false,
            'follow_system' => false,
            'font_key' => ThemePresets::DEFAULT_FONT_KEY,
            'font_size' => ThemePresets::DEFAULT_FONT_SIZE,
        ]);

        return back()->with('success', 'Appearance reset to the default (Ocean Blue, light mode).');
    }

    private function currentUserOrDefaults(): array
    {
        return request()->user()->appearance();
    }
}
