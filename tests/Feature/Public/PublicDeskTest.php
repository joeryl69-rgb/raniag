<?php

use App\Models\Announcement;
use App\Models\PublicHotline;
use App\Models\SystemSetting;
use App\Models\User;

test('the public advisories page lists a published update and hides drafts', function () {
    $admin = User::factory()->administrator()->create();
    Announcement::create([
        'title' => 'River watch for low barangays',
        'body' => 'Stay clear of the riverbank overnight.',
        'is_published' => true,
        'created_by' => $admin->id,
    ]);
    Announcement::create([
        'title' => 'Draft only',
        'body' => 'This should stay inside the office.',
        'is_published' => false,
        'created_by' => $admin->id,
    ]);

    $this->get(route('public.advisories'))
        ->assertOk()
        ->assertSee('River watch for low barangays')
        ->assertDontSee('Draft only');
});

test('an administrator can publish the alert posture and a hotline', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->put(route('admin.public_desk.posture'), [
            'alert_level' => 'blue',
            'alert_note' => 'Prepare go-bags in flood-prone barangays.',
        ])
        ->assertRedirect();

    expect(SystemSetting::current()->alert_level)->toBe('blue');

    $this->actingAs($admin)
        ->post(route('admin.public_desk.hotlines.store'), [
            'name' => 'MDRRMO Operations',
            'number' => '0917 000 0000',
            'detail' => '24/7',
            'is_published' => '1',
        ])
        ->assertRedirect();

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Blue Alert')
        ->assertSee('Prepare go-bags in flood-prone barangays.')
        ->assertSee('MDRRMO Operations')
        ->assertSee('0917 000 0000');

    $hidden = PublicHotline::create([
        'name' => 'Internal only',
        'number' => '0918 111 1111',
        'is_published' => false,
    ]);

    $this->get(route('public.advisories'))
        ->assertOk()
        ->assertSee('MDRRMO Operations')
        ->assertDontSee('Internal only');

    $this->actingAs($admin)
        ->delete(route('admin.public_desk.hotlines.destroy', $hidden))
        ->assertRedirect();

    expect(PublicHotline::query()->where('name', 'Internal only')->exists())->toBeFalse();
});
