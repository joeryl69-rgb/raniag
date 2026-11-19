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
            'homeContent' => $setting->publicHomeContent(false),
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
            'home_content' => ['sometimes', 'array:shell,hero,shortcuts,qr,journey,briefing,faqs,app,support'],
            'home_content.shell' => ['required_with:home_content', 'array:home_title,meta_description,social_description,public_reporting_label,navigation,footer'],
            'home_content.shell.home_title' => ['required_with:home_content', 'string', 'max:120'],
            'home_content.shell.meta_description' => ['required_with:home_content', 'string', 'max:180'],
            'home_content.shell.social_description' => ['required_with:home_content', 'string', 'max:240'],
            'home_content.shell.public_reporting_label' => ['required_with:home_content', 'string', 'max:80'],
            'home_content.shell.navigation' => ['required_with:home_content', 'array:home,track,live_map,community,staff,staff_login,theme,report'],
            'home_content.shell.navigation.*' => ['required', 'string', 'max:80'],
            'home_content.shell.footer' => ['required_with:home_content', 'array:desk_label,community_label,description,report_channel_label,default_report_text,report_section,file_report,track_report,how_it_works,access_section,staff_dashboard,staff_login,replay_tour,support,hotline_label'],
            'home_content.shell.footer.*' => ['required', 'string', 'max:500'],
            'home_content.hero' => ['required_with:home_content', 'array:eyebrow,desk_label,title_lead,title_emphasis,tagline,description,report_button,track_button,latitude,longitude,location_label'],
            'home_content.hero.*' => ['required', 'string', 'max:500'],
            'home_content.shortcuts' => ['required_with:home_content', 'array', 'size:3'],
            'home_content.shortcuts.*' => ['required', 'array:kicker,title'],
            'home_content.shortcuts.*.*' => ['required', 'string', 'max:100'],
            'home_content.qr' => ['required_with:home_content', 'array:eyebrow,stage,title,description,link_label,download_label,code_alt'],
            'home_content.qr.*' => ['required', 'string', 'max:1000'],
            'home_content.journey' => ['required_with:home_content', 'array:eyebrow,sequence,title_lead,title_emphasis,description,visual_label,guide_label,guide_alt,tour_label,tour_hint,steps'],
            'home_content.journey.steps' => ['required_with:home_content', 'array', 'size:4'],
            'home_content.journey.steps.*' => ['required', 'array:eyebrow,title,description,link_label,visual_status,visual_note'],
            'home_content.journey.steps.*.*' => ['required', 'string', 'max:600'],
            'home_content.journey.eyebrow' => ['required_with:home_content', 'string', 'max:100'],
            'home_content.journey.sequence' => ['required_with:home_content', 'string', 'max:40'],
            'home_content.journey.title_lead' => ['required_with:home_content', 'string', 'max:140'],
            'home_content.journey.title_emphasis' => ['required_with:home_content', 'string', 'max:140'],
            'home_content.journey.description' => ['required_with:home_content', 'string', 'max:600'],
            'home_content.journey.visual_label' => ['required_with:home_content', 'string', 'max:100'],
            'home_content.journey.guide_label' => ['required_with:home_content', 'string', 'max:100'],
            'home_content.journey.guide_alt' => ['required_with:home_content', 'string', 'max:180'],
            'home_content.journey.tour_label' => ['required_with:home_content', 'string', 'max:120'],
            'home_content.journey.tour_hint' => ['required_with:home_content', 'string', 'max:120'],
            'home_content.briefing' => ['required_with:home_content', 'array:eyebrow,sequence,title_lead,title_emphasis,description,announcements,faq'],
            'home_content.briefing.eyebrow' => ['required_with:home_content', 'string', 'max:100'],
            'home_content.briefing.sequence' => ['required_with:home_content', 'string', 'max:40'],
            'home_content.briefing.title_lead' => ['required_with:home_content', 'string', 'max:140'],
            'home_content.briefing.title_emphasis' => ['required_with:home_content', 'string', 'max:140'],
            'home_content.briefing.description' => ['required_with:home_content', 'string', 'max:500'],
            'home_content.briefing.announcements' => ['required_with:home_content', 'array:badge,local_desk,title,empty_title,empty_description'],
            'home_content.briefing.announcements.*' => ['required', 'string', 'max:500'],
            'home_content.briefing.faq' => ['required_with:home_content', 'array:badge,title,support_link'],
            'home_content.briefing.faq.*' => ['required', 'string', 'max:160'],
            'home_content.faqs' => ['required_with:home_content', 'array', 'min:1', 'max:12'],
            'home_content.faqs.*' => ['required', 'array:question,answer,link_label'],
            'home_content.faqs.*.question' => ['required', 'string', 'max:180'],
            'home_content.faqs.*.answer' => ['required', 'string', 'max:2000'],
            'home_content.faqs.*.link_label' => ['nullable', 'string', 'max:100'],
            'home_content.app' => ['required_with:home_content', 'array:aside_label,field_tool_label,index,title,description,download_button,illustration_alt,install_success,install_cancelled,already_installed,install_instructions'],
            'home_content.app.*' => ['required', 'string', 'max:400'],
            'home_content.support' => ['required_with:home_content', 'array:index,title,description,button'],
            'home_content.support.*' => ['required', 'string', 'max:500'],
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
        if (array_key_exists('home_content', $validated)) {
            $setting->public_home_content = $validated['home_content'];
        }

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
            'public_home_content' => null,
        ])->save();

        if ($oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()
            ->route('admin.branding.index')
            ->with('success', 'Branding and public page content were reset to their defaults.');
    }
}
