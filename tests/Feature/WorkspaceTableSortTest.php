<?php

use App\Models\Agency;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

test('incident type register renders compact table controls and orders the requested column', function () {
    $administrator = User::factory()->administrator()->create();
    IncidentType::factory()->create(['name' => 'Alpha classification', 'sort_order' => 1]);
    IncidentType::factory()->create(['name' => 'Zulu classification', 'sort_order' => 2]);

    $response = $this->actingAs($administrator)
        ->get(route('admin.incident_types.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertSee('Classification catalog')
        ->assertSee('rg-table-desk', false)
        ->assertSee('rg-register-actions', false)
        ->assertSee('Sort by Name')
        ->assertSee('bi-sort-down', false)
        ->assertDontSee('Sort by Default Priority')
        ->assertDontSee('Sort by In Use')
        ->assertDontSee('Sort by Status')
        ->assertDontSee('data-view-mode-choice="cards"', false);

    expect(strpos($response->getContent(), 'Zulu classification'))
        ->toBeLessThan(strpos($response->getContent(), 'Alpha classification'));
});

test('agency and personnel directories render independently with separate navigation', function () {
    $administrator = User::factory()->administrator()->create();
    Agency::query()->create(['name' => 'Alpha response office', 'code' => 'ARO', 'is_active' => true]);
    Agency::query()->create(['name' => 'Zulu response office', 'code' => 'ZRO', 'is_active' => true]);
    User::factory()->create([
        'name' => 'Personnel Directory Test',
        'email' => 'personnel-directory@example.test',
        'role' => UserRole::Personnel,
        'role_title' => 'Response Officer',
        'team_assignment' => 'Field Team',
    ]);

    $response = $this->actingAs($administrator)
        ->get(route('admin.agencies.index', [
            'directory' => 'agencies',
            'agency_sort' => 'name',
            'agency_direction' => 'desc',
        ]))
        ->assertOk()
        ->assertSee('Agency directory')
        ->assertDontSee('data-view-mode-choice="cards"', false)
        ->assertSee('name="agency_q"', false)
        ->assertSee('agency_sort=name')
        ->assertSee('Sort by Agency')
        ->assertDontSee('Sort by Code')
        ->assertDontSee('Sort by Status')
        ->assertSee('href="'.route('admin.agencies.index', ['directory' => 'personnel']).'"', false)
        ->assertDontSee('id="rg-personnel-tbody"', false);

    expect(strpos($response->getContent(), 'Zulu response office'))
        ->toBeLessThan(strpos($response->getContent(), 'Alpha response office'));

    $this->actingAs($administrator)
        ->get(route('admin.agencies.index', ['directory' => 'personnel']))
        ->assertOk()
        ->assertSee('Personnel directory')
        ->assertSee('Sort by Name')
        ->assertDontSee('Sort by Email')
        ->assertDontSee('Sort by Role title')
        ->assertDontSee('Sort by Team assignment')
        ->assertDontSee('Sort by Status')
        ->assertDontSee('data-view-mode-choice="cards"', false)
        ->assertSee('Personnel Directory Test')
        ->assertSee('Response Officer')
        ->assertSee('Manage roles')
        ->assertSee('id="rg-personnel-tbody"', false)
        ->assertDontSee('id="rg-agencies-tbody"', false)
        ->assertSee('href="'.route('admin.personnel_roles.index').'"', false);
});

test('agency case registers expose their own sortable table controls', function () {
    $agency = Agency::query()->create([
        'name' => 'Sort test response office',
        'code' => 'STRO',
        'is_active' => true,
    ]);
    $user = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $type = IncidentType::factory()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-SORTDOC',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => 'resolved',
        'priority' => 'medium',
        'description' => 'Resolved report used to show request history sorting.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $agency->id,
        'requested_by' => $user->id,
        'request_type' => 'single',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('agency.document_requests.index', ['sort' => 'created_at', 'direction' => 'desc']))
        ->assertOk()
        ->assertSee('Request history')
        ->assertSee('Sort by Requested at')
        ->assertDontSee('Sort by Request type')
        ->assertDontSee('Sort by Status')
        ->assertDontSee('data-view-mode-choice="cards"', false)
        ->assertSee('rg-filter-menu', false);

    $this->actingAs($user)
        ->get(route('agency.archived_reports.index', ['sort' => 'tracking_number', 'direction' => 'asc']))
        ->assertOk()
        ->assertSee('Completed case register')
        ->assertSee('Sort by Reported at')
        ->assertDontSee('Sort by Tracking #')
        ->assertDontSee('Sort by Barangay')
        ->assertDontSee('Sort by Status')
        ->assertDontSee('data-view-mode-choice="cards"', false)
        ->assertSee('rg-filter-menu', false);
});

test('communications, integrity, and role registers render sortable links', function () {
    $administrator = User::factory()->administrator()->create();

    $this->actingAs($administrator)
        ->get(route('admin.sms-logs'))
        ->assertOk()
        ->assertSee('rg-filter-menu', false)
        ->assertSee('rg-register-heading', false)
        ->assertDontSee('data-view-mode-choice="cards"', false)
        ->assertSee('Sort by Sent / attempt')
        ->assertDontSee('Sort by Recipient phone')
        ->assertDontSee('Sort by Provider')
        ->assertDontSee('Sort by Status');

    $this->actingAs($administrator)
        ->get(route('admin.audit-logs'))
        ->assertOk()
        ->assertSee('rg-register-actions', false)
        ->assertDontSee('data-view-mode-choice="cards"', false)
        ->assertSee('Sort by Occurred at')
        ->assertDontSee('Sort by Event')
        ->assertDontSee('Sort by Log name');

    $this->actingAs($administrator)
        ->get(route('admin.personnel_roles.index'))
        ->assertOk()
        ->assertSee('Role title register')
        ->assertSee('Sort by Role title')
        ->assertDontSee('Sort by Status');
});
