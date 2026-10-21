<?php

use App\Models\Notification;
use App\Models\User;

test('notification status filters retain the server-side list and expose smooth filter controls', function () {
    $user = User::factory()->administrator()->create();
    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'incident.new',
        'title' => 'Unread incident alert',
        'message' => 'A new incident needs review.',
    ]);
    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'incident.assigned',
        'title' => 'Read dispatch update',
        'message' => 'A dispatch was assigned.',
        'read_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('notifications.index', ['status' => 'unread']))
        ->assertOk()
        ->assertSee('Unread incident alert')
        ->assertDontSee('Read dispatch update')
        ->assertSee('data-notification-filter', false)
        ->assertSee('id="notification-results"', false)
        ->assertSee('notification-filters.js', false)
        ->assertSee('data-notification-filter class="btn btn-outline-secondary active">Unread', false);
});
