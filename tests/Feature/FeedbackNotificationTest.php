<?php

use App\Models\Notification;
use App\Models\User;

test('public and support center feedback notify active administrators and appear in type filters', function () {
    $administrator = User::factory()->administrator()->create();
    $secondAdministrator = User::factory()->administrator()->create();
    $inactiveAdministrator = User::factory()->administrator()->create(['is_active' => false]);

    $this->post(route('public.feedback.store'), [
        'category' => 'technical',
        'subject' => 'Map is not loading',
        'message' => 'The public map stays blank on my phone.',
        'submitter_name' => 'Community member',
        'submitter_email' => 'member@example.com',
    ])->assertSessionHas('sent', true);

    $notifications = Notification::query()->where('type', 'feedback.new')->get();
    expect($notifications)->toHaveCount(2)
        ->and($notifications->pluck('user_id')->all())->toContain($administrator->id, $secondAdministrator->id)
        ->and($notifications->pluck('user_id'))->not->toContain($inactiveAdministrator->id);

    $publicNotification = $notifications->firstWhere('user_id', $administrator->id);
    expect($publicNotification->title)->toBe('New feedback or concern')
        ->and($publicNotification->message)->toContain('Technical: Map is not loading')
        ->and($publicNotification->url())->toContain('/admin/feedback?')
        ->and($publicNotification->icon())->toBe('bi-chat-square-heart-fill');

    $supportUser = User::factory()->agency()->create();
    $this->actingAs($supportUser)
        ->post(route('agency.support.store'), [
            'category' => 'account',
            'subject' => 'Cannot update account',
            'message' => 'Please help with the account profile.',
        ])->assertRedirect(route('agency.support.create'));

    expect(Notification::query()->where('type', 'feedback.new')->count())->toBe(4)
        ->and(Notification::query()
            ->where('user_id', $administrator->id)
            ->where('type', 'feedback.new')
            ->where('message', 'Account: Cannot update account (Support Center).')
            ->exists())->toBeTrue();

    $this->actingAs($administrator)
        ->get(route('notifications.index', ['type' => 'feedback.new']))
        ->assertOk()
        ->assertSee('Feedback &amp; Concerns', false)
        ->assertSee('Map is not loading')
        ->assertSee('Cannot update account');
});
