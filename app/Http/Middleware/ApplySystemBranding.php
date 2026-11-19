<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemBranding
{
    public function handle(Request $request, Closure $next): Response
    {
        $setting = SystemSetting::query()->find(1) ?? new SystemSetting;
        $branding = $setting->brandingValues();

        config([
            'raniag.name' => $branding['system_name'],
            'raniag.organization' => $branding['organization_name'],
            'raniag.address.municipality' => $branding['municipality_label'],
            'raniag.address.province' => $branding['province_label'],
            'raniag.address.region' => $branding['response_area_label'],
        ]);
        View::share('siteBranding', $branding);
        View::share('publicHomeContent', $setting->publicHomeContent());

        return $next($request);
    }
}
