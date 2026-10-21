<?php

use App\Models\FeedbackSubmission;
use App\Models\User;

test('feedback status category source search and date filters combine correctly', function () {
    $administrator = User::factory()->administrator()->create();

    $makeSubmission = function (array $attributes, int $daysAgo): FeedbackSubmission {
        $submission = FeedbackSubmission::query()->create([
            'category' => 'login',
            'submitted_via' => 'public',
            'subject' => 'Password access concern',
            'message' => 'I need help with a password reset.',
            'submitter_name' => 'Community member',
            'status' => 'new',
            ...$attributes,
        ]);
        $createdAt = now()->subDays($daysAgo);
        $submission->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        return $submission;
    };

    $matching = $makeSubmission([], 4);
    $makeSubmission([
        'category' => 'login',
        'submitted_via' => 'agency',
        'status' => 'reviewed',
    ], 2);
    $makeSubmission([
        'category' => 'technical',
        'submitted_via' => 'public',
        'status' => 'new',
    ], 3);

    $response = $this->actingAs($administrator)
        ->get(route('admin.feedback.index', [
            'q' => 'password',
            'status' => 'new',
            'category' => 'login',
            'source' => 'public',
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->subDays(3)->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('name="status"', false)
        ->assertSee('name="category"', false)
        ->assertSee('name="source"', false)
        ->assertSee('name="date_from"', false)
        ->assertSee('name="date_to"', false)
        ->assertSee('<details class="rg-fb-manage">', false)
        ->assertSee('Manage response')
        ->assertSee('js/modal-root.js', false);

    expect($response->viewData('submissions')->getCollection()->modelKeys())
        ->toBe([$matching->id]);
});
