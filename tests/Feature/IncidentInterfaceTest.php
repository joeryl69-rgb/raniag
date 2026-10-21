<?php

use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Agency;
use App\Models\Evidence;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('administrator incident queue restores summary cards and an icon-filtered sortable table', function () {
    $administrator = User::factory()->administrator()->create();

    $this->actingAs($administrator)
        ->get(route('admin.incidents.index'))
        ->assertOk()
        ->assertSee('Incident queue')
        ->assertSee('data-view-mode-choice="table"', false)
        ->assertSee('data-view-mode-choice="cards"', false)
        ->assertSee('kpi-grid', false)
        ->assertSee('All incidents')
        ->assertSee('rg-incidents-table', false)
        ->assertSee('Open incident filters')
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('class="mobile-dock d-lg-none"', false)
        ->assertDontSee('mobile-dock--persistent', false)
        ->assertDontSee('incident-queue-page', false)
        ->assertDontSee('syncDockToViewport', false)
        ->assertDontSee('viewport.offsetLeft', false)
        ->assertSee('orientation: landscape', false)
        ->assertSee('isEditable(document.activeElement)', false)
        ->assertSee('viewport-fit=cover', false)
        ->assertSee('type="date"', false)
        ->assertDontSee('rg-incident-shortcuts', false)
        ->assertDontSee('DISPATCH DOCKET');
});

test('administrator incident queue filters by priority and exposes the selected sort', function () {
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();
    $makeIncident = function (string $trackingNumber, string $priority) use ($type): Incident {
        return Incident::query()->create([
            'tracking_number' => $trackingNumber,
            'tracking_pin' => Hash::make('123456'),
            'incident_type_id' => $type->id,
            'status' => 'submitted',
            'priority' => $priority,
            'description' => "Queue filter test {$trackingNumber}",
            'is_anonymous' => true,
            'reported_at' => now(),
        ]);
    };
    $makeIncident('RAN-QUEUE-HIGH', 'high');
    $makeIncident('RAN-QUEUE-LOW', 'low');

    $this->actingAs($administrator)
        ->get(route('admin.incidents.index', [
            'priority' => 'high',
            'sort' => 'tracking_number',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertSee('RAN-QUEUE-HIGH')
        ->assertDontSee('RAN-QUEUE-LOW')
        ->assertSee('rg-incidents-table', false)
        ->assertSee('name="priority"', false)
        ->assertSee('value="high" selected', false)
        ->assertSee('Sort by Priority')
        ->assertSee('Sort by Reported')
        ->assertDontSee('Sort by Incident / tracking')
        ->assertDontSee('Sort by Response status')
        ->assertDontSee('Sort by Location')
        ->assertDontSee('>Response state<', false)
        ->assertDontSee('>Tracking number<', false);
});

test('all incident queues show newest reports first by default and retain explicit priority sorting', function () {
    $administrator = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'Priority Response Unit',
        'code' => 'PRU',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $personnel = User::factory()->create(['role' => UserRole::Personnel]);
    $type = IncidentType::factory()->create(['name' => 'Flood']);

    foreach ([
        ['CRITICAL-OLD', 'critical', 60],
        ['CRITICAL-NEW', 'critical', 30],
        ['HIGH-ONLY', 'high', 20],
        ['MEDIUM-ONLY', 'medium', 10],
        ['LOW-ONLY', 'low', 1],
    ] as [$reference, $priority, $minutesAgo]) {
        $incident = Incident::query()->create([
            'tracking_number' => 'RAN-ORDER-'.$reference,
            'tracking_pin' => Hash::make('123456'),
            'incident_type_id' => $type->id,
            'status' => 'assigned',
            'priority' => $priority,
            'title' => null,
            'description' => 'Flooded road report '.$reference,
            'is_anonymous' => true,
            'reported_at' => now()->subMinutes($minutesAgo),
        ]);

        Assignment::query()->create([
            'incident_id' => $incident->id,
            'agency_id' => $agency->id,
            'assigned_by' => $administrator->id,
            'assigned_at' => now(),
        ]);
        Assignment::query()->create([
            'incident_id' => $incident->id,
            'assigned_to' => $personnel->id,
            'assigned_by' => $administrator->id,
            'assigned_at' => now(),
        ]);
    }

    $queues = [
        [$administrator, 'admin.incidents.index'],
        [$agencyUser, 'agency.incidents.index'],
        [$personnel, 'personnel.incidents.index'],
    ];
    $newestFirstReferences = [
        'RAN-ORDER-LOW-ONLY',
        'RAN-ORDER-MEDIUM-ONLY',
        'RAN-ORDER-HIGH-ONLY',
        'RAN-ORDER-CRITICAL-NEW',
        'RAN-ORDER-CRITICAL-OLD',
    ];

    foreach ($queues as [$user, $routeName]) {
        $response = $this->actingAs($user)->get(route($routeName))->assertOk();
        $content = $response->getContent();
        $positions = array_map(fn ($reference) => strpos($content, $reference), $newestFirstReferences);
        $sortedPositions = $positions;
        sort($sortedPositions);

        expect($positions)->toBe($sortedPositions)
            ->and($content)->toContain('value="reported_at" selected')
            ->and($content)->toContain('Sort by')
            ->and($content)->toContain('Newest first')
            ->and($content)->toContain('Flood report');

        $priorityResponse = $this->get(route($routeName, ['sort' => 'priority', 'direction' => 'asc']))
            ->assertOk()
            ->assertSee('Flood report')
            ->assertSee('value="priority" selected', false);
        $priorityContent = $priorityResponse->getContent();

        expect(strpos($priorityContent, 'RAN-ORDER-CRITICAL-OLD'))
            ->toBeLessThan(strpos($priorityContent, 'RAN-ORDER-CRITICAL-NEW'))
            ->and(strpos($priorityContent, 'RAN-ORDER-CRITICAL-NEW'))
            ->toBeLessThan(strpos($priorityContent, 'RAN-ORDER-HIGH-ONLY'));
    }
});

test('administrator agency and personnel queues render working pagination on later pages', function () {
    $administrator = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'Pagination Response Unit',
        'code' => 'PRU-PG',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $personnel = User::factory()->create([
        'role' => UserRole::Personnel,
        'agency_id' => $agency->id,
    ]);
    $type = IncidentType::factory()->create(['name' => 'Pagination Test']);

    foreach (range(1, 16) as $index) {
        $incident = Incident::query()->create([
            'tracking_number' => sprintf('RAN-PAGINATION-%02d', $index),
            'tracking_pin' => Hash::make('123456'),
            'incident_type_id' => $type->id,
            'status' => 'assigned',
            'priority' => 'medium',
            'description' => "Pagination test incident {$index}.",
            'is_anonymous' => true,
            'reported_at' => now()->subMinutes($index),
        ]);

        Assignment::query()->create([
            'incident_id' => $incident->id,
            'agency_id' => $agency->id,
            'assigned_by' => $administrator->id,
            'assigned_at' => now(),
        ]);
        Assignment::query()->create([
            'incident_id' => $incident->id,
            'assigned_to' => $personnel->id,
            'assigned_by' => $administrator->id,
            'assigned_at' => now(),
        ]);
    }

    foreach ([
        [$administrator, 'admin.incidents.index'],
        [$agencyUser, 'agency.incidents.index'],
        [$personnel, 'personnel.incidents.index'],
    ] as [$user, $routeName]) {
        $this->actingAs($user)
            ->get(route($routeName, ['page' => 2]))
            ->assertOk()
            ->assertSee('class="pagination"', false)
            ->assertSee('page-item active', false)
            ->assertSee('rel="prev"', false)
            ->assertSee('RAN-PAGINATION-16');
    }
});

test('agency and personnel accept actions keep a readable button width', function () {
    $administrator = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'Action Button Response Unit',
        'code' => 'ABRU',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $personnel = User::factory()->create([
        'role' => UserRole::Personnel,
        'agency_id' => $agency->id,
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ACK-BUTTON',
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => 'assigned',
        'priority' => 'high',
        'description' => 'Accept button width regression test.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'assigned_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'assigned_to' => $personnel->id,
        'assigned_by' => $administrator->id,
        'assigned_at' => now(),
    ]);

    foreach ([
        [$agencyUser, 'agency.incidents.show'],
        [$personnel, 'personnel.incidents.show'],
    ] as [$user, $routeName]) {
        $this->actingAs($user)
            ->get(route($routeName, $incident->id))
            ->assertOk()
            ->assertSee('rg-accept-dispatch', false)
            ->assertSee('data-live-refresh-target="#rg-case-hero"', false)
            ->assertSee('data-live-refresh-interval="4000"', false)
            ->assertSee('id="case-history" data-live-refresh', false)
            ->assertDontSee('btn-lg w-100" data-bs-toggle="modal" data-bs-target="#acceptAcknowledgeModal"', false);
    }
});

test('case files share responsive evidence previews and hide GPS upload filenames across roles', function () {
    $administrator = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'Evidence Preview Response Unit',
        'code' => 'EPRU',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $personnel = User::factory()->create([
        'role' => UserRole::Personnel,
        'agency_id' => $agency->id,
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-EVIDENCE-PREVIEW',
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => 'assigned',
        'priority' => 'medium',
        'description' => '',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'assigned_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'assigned_to' => $personnel->id,
        'assigned_by' => $administrator->id,
        'assigned_at' => now(),
    ]);
    Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'photo',
        'file_path' => 'incidents/'.$incident->id.'/evidence/gps-photo.jpg',
        'original_filename' => 'gps-1792546759865.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024,
        'priority' => 1,
        'is_gps_capture' => true,
    ]);
    Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'photo',
        'file_path' => 'incidents/'.$incident->id.'/evidence/standard-photo.jpg',
        'original_filename' => 'gps-1792546759865.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024,
        'priority' => 2,
        'is_gps_capture' => false,
    ]);

    foreach ([
        [$administrator, 'admin.incidents.show'],
        [$agencyUser, 'agency.incidents.show'],
        [$personnel, 'personnel.incidents.show'],
    ] as [$user, $routeName]) {
        $this->actingAs($user)
            ->get(route($routeName, $incident))
            ->assertOk()
            ->assertSee('No repository documents attached for the selected content.')
            ->assertSee('GPS Photo')
            ->assertSee('Attached photo')
            ->assertSee('rg-evidence-lightbox-dialog', false)
            ->assertDontSee('gps-1792546759865.jpg');
    }

    $lightbox = file_get_contents(resource_path('views/components/lightbox.blade.php'));
    $incidentStyles = file_get_contents(public_path('css/incidents.css'));
    $workspaceStyles = file_get_contents(public_path('css/workspace.css'));
    $publicTrackingView = file_get_contents(resource_path('views/public/track/show.blade.php'));
    expect($lightbox)->not->toContain('modal-xl')
        ->not->toContain('min-height:60vh')
        ->and($incidentStyles)->toContain('object-fit: contain')
        ->toContain('height: clamp(9rem, 22vw, 15rem)')
        ->toContain('.rg-incident-detail #case-evidence .evidence-img-container.evidence-video-container')
        ->and($workspaceStyles)->toContain('.rg-incident-detail .case-map-frame .leaflet-top.leaflet-right')
        ->toContain('right: .5rem')
        ->toContain('.rg-agency-case-layout #case-evidence .evidence-video-container')
        ->toContain('.rg-archived-evidence-grid .rg-evidence-video-player')
        ->and($publicTrackingView)->toContain('.rg-track-evidence-grid img,')
        ->toContain('.rg-track-evidence-grid .rg-evidence-video-player { width:min(100%, 210px); height:150px; }');
});

test('agency and personnel incident lists use the same deliberate filter workflow', function () {
    $agency = Agency::query()->create([
        'name' => 'Pamplona Response Unit',
        'code' => 'PRU',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $personnel = User::factory()->create(['role' => UserRole::Personnel]);

    $this->actingAs($agencyUser)
        ->get(route('agency.incidents.index'))
        ->assertOk()
        ->assertSee('Assigned emergency responses')
        ->assertSee('rg-docket-list', false)
        ->assertSee('Open incident filters')
        ->assertSee('type="date"', false);

    $this->actingAs($personnel)
        ->get(route('personnel.incidents.index'))
        ->assertOk()
        ->assertSee('My active dispatches')
        ->assertSee('rg-docket-list', false)
        ->assertSee('Open incident filters')
        ->assertSee('type="date"', false);
});
