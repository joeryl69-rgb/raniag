<?php

use App\Models\Announcement;
use App\Models\User;

test('an administrator can open and save an existing announcement', function () {
    $admin = User::factory()->administrator()->create();
    $announcement = Announcement::create([
        'title' => 'reporting process',
        'badge' => 'New Feature',
        'body' => 'hindi na redundant okay na sya',
        'icon' => 'bi-stars',
        'is_published' => true,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.announcements.index'))
        ->assertOk()
        ->assertSee('data-ann-edit=', false)
        ->assertSee('reporting process', false)
        ->assertDontSee("openEditModal(@", false);

    $this->actingAs($admin)
        ->put(route('admin.announcements.update', $announcement), [
            'title' => 'Updated reporting process',
            'badge' => 'New Feature',
            'body' => 'The edited message is saved.',
            'icon' => 'bi-stars',
            'is_published' => '1',
        ])
        ->assertRedirect();

    expect($announcement->fresh()->title)->toBe('Updated reporting process');
});

test('the public homepage shows the full announcement text', function () {
    $body = 'In our best efforts to provide an excellent service to all of our users, we are planning to perform system upgrade on Monday at 8:00 PM. The process will continue until the upgrade window closes and services return.';

    Announcement::create([
        'title' => 'System Upgrade Notification Letter',
        'badge' => 'Maintenance',
        'body' => $body,
        'is_published' => true,
    ]);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee($body, false)
        ->assertDontSee('The process wi...', false);
});
