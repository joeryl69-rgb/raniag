<?php

use App\Models\Announcement;
use App\Models\User;

function makeFilteredAnnouncement(array $attributes, int $daysAgo): Announcement
{
    $announcement = Announcement::create([
        'title' => 'General notice',
        'badge' => 'Update',
        'body' => 'A short public notice.',
        'is_published' => true,
        ...$attributes,
    ]);
    $announcement->forceFill(['published_at' => now()->subDays($daysAgo)])->saveQuietly();

    return $announcement;
}

test('admin announcement filters combine search type status and dates', function () {
    $administrator = User::factory()->administrator()->create();

    makeFilteredAnnouncement([
        'title' => 'System upgrade window',
        'badge' => 'Maintenance',
        'body' => 'The process will take around two hours.',
        'is_published' => true,
    ], 4);
    makeFilteredAnnouncement([
        'title' => 'System upgrade draft',
        'badge' => 'Maintenance',
        'body' => 'Hidden copy of the upgrade notice.',
        'is_published' => false,
    ], 4);
    makeFilteredAnnouncement([
        'title' => 'Community safety reminder',
        'badge' => 'Safety Alert',
        'body' => 'Stay indoors during the storm.',
        'is_published' => true,
    ], 4);
    makeFilteredAnnouncement([
        'title' => 'Old maintenance note',
        'badge' => 'Maintenance',
        'body' => 'This upgrade already finished.',
        'is_published' => true,
    ], 20);

    $this->actingAs($administrator)
        ->get(route('admin.announcements.index', [
            'q' => 'upgrade',
            'type' => 'Maintenance',
            'status' => 'published',
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->subDays(3)->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('name="q"', false)
        ->assertSee('name="type"', false)
        ->assertSee('name="status"', false)
        ->assertSee('name="date_from"', false)
        ->assertSee('All types')
        ->assertSee('All statuses')
        ->assertSee('System upgrade window')
        ->assertDontSee('System upgrade draft')
        ->assertDontSee('Community safety reminder')
        ->assertDontSee('Old maintenance note');
});

test('the public updates list uses the same filters and keeps the latest three by default', function () {
    $older = makeFilteredAnnouncement([
        'title' => 'Earlier maintenance letter',
        'badge' => 'Maintenance',
        'body' => 'The earlier maintenance letter stays available when that type is selected.',
    ], 30);
    makeFilteredAnnouncement(['title' => 'Newest update', 'badge' => 'Update'], 1);
    makeFilteredAnnouncement(['title' => 'Middle alert', 'badge' => 'Safety Alert'], 2);
    makeFilteredAnnouncement(['title' => 'Recent reminder', 'badge' => 'Reminder'], 3);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Search title or message')
        ->assertSee('All types')
        ->assertSee('Newest update')
        ->assertSee('Middle alert')
        ->assertSee('Recent reminder')
        ->assertDontSee('Earlier maintenance letter');

    $this->get(route('public.home', ['type' => 'Maintenance']))
        ->assertOk()
        ->assertSee($older->body, false)
        ->assertSee('Earlier maintenance letter')
        ->assertDontSee('Newest update')
        ->assertDontSee('Middle alert');
});
