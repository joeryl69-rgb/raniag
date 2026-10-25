<?php

use App\Models\Announcement;
use App\Models\FeedbackSubmission;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function makeQueueIncident(IncidentType $type, string $status = 'submitted', string $priority = 'medium'): Incident
{
    return Incident::query()->create([
        'tracking_number' => 'RAN-OPT-'.strtoupper(substr(uniqid(), -6)),
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => $type->id,
        'status' => $status,
        'priority' => $priority,
        'description' => 'Filter option coverage incident.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
}

test('incident filters only offer statuses categories and priorities that exist', function () {
    $administrator = User::factory()->administrator()->create();
    $usedType = IncidentType::factory()->create(['name' => 'Queue Flood', 'slug' => 'queue-flood']);
    IncidentType::factory()->create(['name' => 'Queue Quarry', 'slug' => 'queue-quarry']);
    makeQueueIncident($usedType);

    $response = $this->actingAs($administrator)
        ->get(route('admin.incidents.index'))
        ->assertOk()
        ->assertSee('value="submitted"', false)
        ->assertSee('Queue Flood')
        ->assertSee('value="medium"', false)
        ->assertDontSee('value="received"', false)
        ->assertDontSee('value="assigned"', false)
        ->assertDontSee('value="in_progress"', false)
        ->assertDontSee('value="pending_info"', false)
        ->assertDontSee('value="resolved"', false)
        ->assertDontSee('value="closed"', false)
        ->assertDontSee('value="rejected"', false)
        ->assertDontSee('value="outside_aor"', false)
        ->assertDontSee('Queue Quarry')
        ->assertDontSee('value="critical"', false)
        ->assertDontSee('value="high"', false)
        ->assertDontSee('value="low"', false);

    $response->assertDontSee('Not resolved', false)
        ->assertDontSee('Newest open incidents', false)
        ->assertSee('>Active cases<', false)
        ->assertDontSee('hidden></option>', false)
        ->assertDontSee('>All statuses<', false)
        ->assertDontSee('Resolved or closed', false)
        ->assertDontSee('value="finished"', false)
        ->assertDontSee('value="all"', false);

    $this->get(route('admin.incidents.index', ['status' => 'received']))
        ->assertOk()
        ->assertDontSee('value="received"', false)
        ->assertSee('value="submitted"', false);
});

test('the admin incident queue hides resolved reports until that status is chosen', function () {
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();
    $open = makeQueueIncident($type, 'submitted', 'high');
    $open->update(['tracking_number' => 'RAN-OPEN-NEW', 'reported_at' => now()->subHour()]);
    $resolved = makeQueueIncident($type, 'resolved', 'medium');
    $resolved->update(['tracking_number' => 'RAN-DONE-NEW', 'reported_at' => now()]);
    $closed = makeQueueIncident($type, 'closed', 'low');
    $closed->update(['tracking_number' => 'RAN-CLOSED-NEW', 'reported_at' => now()]);

    $this->actingAs($administrator)
        ->get(route('admin.incidents.index'))
        ->assertOk()
        ->assertSee('RAN-OPEN-NEW')
        ->assertDontSee('RAN-DONE-NEW')
        ->assertDontSee('RAN-CLOSED-NEW')
        ->assertSee('value="submitted"', false)
        ->assertSee('value="resolved"', false)
        ->assertSee('value="closed"', false)
        ->assertDontSee('Not resolved', false)
        ->assertDontSee('Newest open incidents', false)
        ->assertDontSee('value="finished"', false);

    $this->get(route('admin.incidents.index', ['status' => 'resolved']))
        ->assertOk()
        ->assertSee('RAN-DONE-NEW')
        ->assertDontSee('RAN-OPEN-NEW')
        ->assertDontSee('RAN-CLOSED-NEW')
        ->assertSee('value="resolved" selected', false);
});

test('the situational map status and priority filters follow open incidents', function () {
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();
    makeQueueIncident($type, 'submitted', 'high');
    makeQueueIncident($type, 'resolved', 'low');

    $this->actingAs($administrator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('value="submitted"', false)
        ->assertSee('value="high"', false)
        ->assertDontSee('value="received"', false)
        ->assertDontSee('value="pending_info"', false)
        ->assertDontSee('value="low"', false);
});

test('case document filters omit statuses and completion states with no files', function () {
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();
    makeQueueIncident($type, 'resolved');

    $this->actingAs($administrator)
        ->get(route('admin.incident_documents.index'))
        ->assertOk()
        ->assertSee('value="resolved"', false)
        ->assertSee('value="missing"', false)
        ->assertDontSee('value="closed"', false)
        ->assertDontSee('value="complete"', false)
        ->assertDontSee('All finished cases', false)
        ->assertDontSee('Active cases', false)
        ->assertDontSee('value="all"', false)
        ->assertDontSee('value="submitted"', false)
        ->assertDontSee('value="in_progress"', false);
});

test('case document status filter lists only statuses that have cases', function () {
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();
    makeQueueIncident($type, 'resolved');
    makeQueueIncident($type, 'closed');
    makeQueueIncident($type, 'submitted');

    $html = $this->actingAs($administrator)
        ->get(route('admin.incident_documents.index'))
        ->assertOk()
        ->getContent();

    preg_match('/<select name="status".*?<\/select>/s', $html, $status);

    expect($status[0] ?? '')
        ->toContain('value="resolved"')
        ->toContain('value="closed"')
        ->not->toContain('value="submitted"')
        ->not->toContain('value="all"')
        ->not->toContain('Active cases')
        ->not->toContain('All finished cases');
});

test('announcement filters omit types and publication states that have no notices', function () {
    $administrator = User::factory()->administrator()->create();
    Announcement::query()->create([
        'title' => 'Posted update',
        'badge' => 'Update',
        'body' => 'A published update.',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.announcements.index'))
        ->assertOk()
        ->assertSee('value="Update"', false)
        ->assertSee('value="published"', false)
        ->assertDontSee('value="Maintenance"', false)
        ->assertDontSee('value="hidden"', false);
});

test('feedback filters omit statuses categories and sources with no messages', function () {
    $administrator = User::factory()->administrator()->create();
    FeedbackSubmission::query()->create([
        'category' => 'login',
        'submitted_via' => 'public',
        'subject' => 'Cannot sign in',
        'message' => 'The password is rejected.',
        'submitter_name' => 'Community member',
        'status' => 'new',
    ]);

    $html = $this->actingAs($administrator)
        ->get(route('admin.feedback.index'))
        ->assertOk()
        ->getContent();

    preg_match('/<select name="status" class="form-select" data-filter-default="new">.*?<\/select>/s', $html, $status);
    preg_match('/<select name="category" class="form-select" data-filter-default="all">.*?<\/select>/s', $html, $category);
    preg_match('/<select name="source" class="form-select" data-filter-default="all">.*?<\/select>/s', $html, $source);

    expect($status[0] ?? '')->toContain('value="new"')->not->toContain('value="reviewed"')->not->toContain('value="resolved"')
        ->and($category[0] ?? '')->toContain('value="login"')->not->toContain('value="technical"')
        ->and($source[0] ?? '')->toContain('value="public"')->not->toContain('value="agency"');
});
