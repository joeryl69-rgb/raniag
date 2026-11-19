<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class SystemBrandingController extends Controller
{
    public function index(): View
    {
        $setting = SystemSetting::current();

        return view('admin.branding.index', [
            'setting' => $setting,
            'branding' => $setting->brandingValues(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_name' => ['required', 'string', 'max:80'],
            'organization_name' => ['required', 'string', 'max:160'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'page_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_text' => ['nullable', 'string', 'max:180'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'show_header_text' => ['nullable', 'boolean'],
            'show_footer_text' => ['nullable', 'boolean'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_hotline' => ['nullable', 'string', 'max:40'],
            'municipality_label' => ['required', 'string', 'max:120'],
            'province_label' => ['required', 'string', 'max:120'],
            'response_area_label' => ['required', 'string', 'max:120'],
        ]);

        $setting = SystemSetting::current();
        $oldLogoPath = $setting->logo_path;
        $newLogoPath = null;
        if (isset($validated['logo'])) {
            $storedPath = $validated['logo']->storePublicly('branding', 'public');
            if (! is_string($storedPath)) {
                throw new RuntimeException('The uploaded logo could not be stored.');
            }
            $newLogoPath = $storedPath;
        }

        $setting->fill(Arr::only($validated, [
            'system_name',
            'organization_name',
            'primary_color',
            'accent_color',
            'page_background_color',
            'header_text',
            'footer_text',
            'contact_address',
            'contact_phone',
            'contact_email',
            'contact_hotline',
            'municipality_label',
            'province_label',
            'response_area_label',
        ]));
        $setting->show_header_text = $request->boolean('show_header_text');
        $setting->show_footer_text = $request->boolean('show_footer_text');

        if ($newLogoPath !== null) {
            $setting->logo_path = $newLogoPath;
        } elseif ($request->boolean('remove_logo')) {
            $setting->logo_path = null;
        }

        $setting->save();

        if ($oldLogoPath && ($newLogoPath !== null || $request->boolean('remove_logo'))) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()
            ->route('admin.branding.index')
            ->with('success', 'Branding and system details were updated.');
    }

    public function reset(): RedirectResponse
    {
        $setting = SystemSetting::current();
        $oldLogoPath = $setting->logo_path;
        $defaults = config('system_branding');

        $setting->fill([
            'system_name' => $defaults['name'],
            'organization_name' => $defaults['organization'],
            'logo_path' => null,
            'primary_color' => $defaults['primary_color'],
            'accent_color' => $defaults['accent_color'],
            'page_background_color' => $defaults['page_background_color'],
            'header_text' => null,
            'footer_text' => null,
            'show_header_text' => true,
            'show_footer_text' => true,
            'contact_address' => null,
            'contact_phone' => null,
            'contact_email' => null,
            'contact_hotline' => null,
            'municipality_label' => $defaults['municipality'],
            'province_label' => $defaults['province'],
            'response_area_label' => $defaults['response_area'],
        ])->save();

        if ($oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()
            ->route('admin.branding.index')
            ->with('success', 'Branding settings were reset to their defaults.');
    }
}
