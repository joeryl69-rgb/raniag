<?php

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public branding defaults do not create the singleton settings row', function () {
    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Share a clear location and incident details so responders can review and route reports.');

    expect(SystemSetting::query()->exists())->toBeFalse();
});

test('only administrators can open and update global branding settings', function () {
    $staff = User::factory()->agency()->create();

    $this->actingAs($staff)
        ->get(route('admin.branding.index'))
        ->assertForbidden();

    $this->get(route('settings.appearance.index'))
        ->assertOk()
        ->assertDontSee(route('admin.branding.index'));

    $this->put(route('admin.branding.update'), [])
        ->assertForbidden();
});

test('administrator branding updates apply to the public shell without changing personal appearance or boundary data', function () {
    $admin = User::factory()->administrator()->create();
    $setting = SystemSetting::query()->create([
        'id' => 1,
        'municipality_boundary' => [
            'type' => 'Polygon',
            'coordinates' => [[[121.3, 18.4], [121.4, 18.4], [121.4, 18.5], [121.3, 18.4]]],
        ],
    ]);

    $payload = [
        'system_name' => 'Rescue Desk',
        'organization_name' => 'Municipal Response Office',
        'primary_color' => '#123456',
        'accent_color' => '#abcdef',
        'page_background_color' => '#f1f2f3',
        'header_text' => 'Emergency information',
        'footer_text' => 'Contact our response team.',
        'show_header_text' => '1',
        'show_footer_text' => '1',
        'contact_address' => 'Main Response Center',
        'contact_phone' => '09170000000',
        'contact_email' => 'response@example.test',
        'contact_hotline' => '911',
        'municipality_label' => 'New Municipality',
        'province_label' => 'New Province',
        'response_area_label' => 'Northern District',
    ];

    $this->actingAs($admin)
        ->get(route('admin.branding.index'))
        ->assertOk()
        ->assertSee('Branding & System Settings')
        ->assertSee(route('admin.branding.index'));

    $this->put(route('admin.branding.update'), $payload)
        ->assertRedirect(route('admin.branding.index'));

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Rescue Desk')
        ->assertSee('Municipal Response Office')
        ->assertSee('Emergency information')
        ->assertSee('Contact our response team.')
        ->assertSee('New Municipality, New Province')
        ->assertSee('--rg-brand: #123456', false)
        ->assertSee('--rg-brand-soft: #abcdef', false)
        ->assertSee('--bs-primary-rgb: 18, 52, 86', false)
        ->assertSee('response@example.test');

    $this->get(route('public.report.create'))
        ->assertOk()
        ->assertSee('RESCUE DESK GPS CAMERA');

    expect($setting->fresh()->municipality_boundary)->toBe([
        'type' => 'Polygon',
        'coordinates' => [[[121.3, 18.4], [121.4, 18.4], [121.4, 18.5], [121.3, 18.4]]],
    ]);

    $this->get(route('settings.appearance.index'))
        ->assertOk()
        ->assertSee('PERSONAL PREFERENCES')
        ->assertSee('Rescue Desk · Municipal Response Office')
        ->assertSee('--raniag-primary: #123456', false)
        ->assertSee('name="theme_key" id="selectedThemeKey" value="ocean"', false)
        ->assertSee(route('admin.branding.index'));
});

test('administrator can upload and remove the public system logo', function () {
    Storage::fake('public');
    $admin = User::factory()->administrator()->create();
    $payload = [
        'system_name' => 'RANIAG',
        'organization_name' => 'MDRRMO Pamplona',
        'primary_color' => '#086b78',
        'accent_color' => '#29aa96',
        'page_background_color' => '#eaf0f2',
        'municipality_label' => 'Pamplona',
        'province_label' => 'Cagayan',
        'response_area_label' => 'Cagayan Valley',
        'logo' => UploadedFile::fake()->image('logo.png'),
    ];

    $this->actingAs($admin)
        ->put(route('admin.branding.update'), $payload)
        ->assertRedirect(route('admin.branding.index'));

    $logoPath = SystemSetting::current()->logo_path;
    expect($logoPath)->not->toBeNull();
    Storage::disk('public')->assertExists($logoPath);

    unset($payload['logo']);
    $payload['remove_logo'] = '1';

    $this->put(route('admin.branding.update'), $payload)
        ->assertRedirect(route('admin.branding.index'));

    expect(SystemSetting::current()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($logoPath);
});

test('branding settings reject unsafe colors and unsupported logo formats', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->from(route('admin.branding.index'))
        ->put(route('admin.branding.update'), [
            'system_name' => 'Brand',
            'organization_name' => 'Organization',
            'primary_color' => 'red;',
            'accent_color' => '#29aa96',
            'page_background_color' => '#eaf0f2',
            'municipality_label' => 'Pamplona',
            'province_label' => 'Cagayan',
            'response_area_label' => 'Cagayan Valley',
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors(['primary_color', 'logo']);

    expect(SystemSetting::query()->first()?->primary_color)->not->toBe('red;');
});
