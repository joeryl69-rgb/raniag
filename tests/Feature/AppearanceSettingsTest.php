<?php

use App\Models\User;
use App\Support\ThemePresets;

test('system settings can follow the device color mode', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put(route('settings.appearance.update'), [
            'theme_key' => 'ocean',
            'follow_system' => '1',
            'font_key' => 'figtree',
            'font_size' => 'normal',
        ]);

    $response->assertRedirect();
    $this->assertTrue($user->refresh()->follow_system);
    $this->assertFalse($user->dark_mode);
});

test('system settings screen and profile password controls render', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.appearance.index'))
        ->assertOk()
        ->assertSee('System Settings')
        ->assertSee('Follow your device')
        ->assertSee("root.style.setProperty('--raniag-card', '#ffffff')", false)
        ->assertDontSee("root.style.removeProperty('--raniag-card')", false);

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Change password')
        ->assertSee('data-password-toggle', false)
        ->assertSee('update_password_current_password');
});

test('device-following settings provide a dark palette override', function () {
    $css = ThemePresets::cssVariables('ocean', false, 'figtree', 'normal', true);

    expect($css)->toContain(':root[data-theme="dark"]')
        ->toContain('--raniag-surface: #0b1220');
});

test('light appearance provides opaque shared workspace surfaces and readable text tokens', function () {
    $css = ThemePresets::cssVariables('ocean');

    expect($css)
        ->toContain('--raniag-card: #ffffff')
        ->toContain('--raniag-card-raised: #f8fafc')
        ->toContain('--raniag-ink: #0f172a')
        ->toContain('--raniag-muted: #64748b');
});
