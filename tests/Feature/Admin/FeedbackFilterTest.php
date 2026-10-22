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

test('feedback opens on new messages', function () {
    $administrator = User::factory()->administrator()->create();

    FeedbackSubmission::query()->create([
        'category' => 'login',
        'submitted_via' => 'public',
        'subject' => 'New sign-in concern',
        'message' => 'I cannot sign in.',
        'submitter_name' => 'Community member',
        'status' => 'new',
    ]);
    FeedbackSubmission::query()->create([
        'category' => 'technical',
        'submitted_via' => 'public',
        'subject' => 'Already reviewed concern',
        'message' => 'This one was already looked at.',
        'submitter_name' => 'Community member',
        'status' => 'reviewed',
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.feedback.index'))
        ->assertOk()
        ->assertSee('New sign-in concern')
        ->assertDontSee('Already reviewed concern')
        ->assertSee('value="new" selected', false);
});

test('every feedback card stays inside the list', function () {
    $administrator = User::factory()->administrator()->create();

    foreach (range(1, 5) as $index) {
        FeedbackSubmission::query()->create([
            'category' => 'account',
            'submitted_via' => 'public',
            'subject' => "Feedback card {$index}",
            'message' => "Message {$index}",
            'submitter_name' => 'Community member',
            'submitter_email' => 'member@example.com',
            'status' => 'reviewed',
        ]);
    }

    $html = $this->actingAs($administrator)
        ->get(route('admin.feedback.index', ['status' => 'all']))
        ->assertOk()
        ->getContent();

    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($document);
    $insideList = $xpath->query('//*[@id="rg-feedback-list"]/article[contains(@class, "rg-fb-case")]');
    $anywhere = $xpath->query('//article[contains(@class, "rg-fb-case")]');
    $besideContent = $xpath->query('//*[@id="wrapper"]/*[contains(@class, "rg-fb-case")]');

    expect($insideList->length)->toBe(5)
        ->and($anywhere->length)->toBe(5)
        ->and($besideContent->length)->toBe(0);
});
