<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Notification;
use App\Models\User;
use App\Services\DashboardAlertFeed;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

test('each staff dashboard polls only notifications addressed to its signed-in account', function () {
    $type = IncidentType::factory()->create(['name' => 'Flood']);
    $admin = User::factory()->administrator()->create();
    $otherAdmin = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'Pamplona Rescue',
        'code' => 'PRES',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $personnel = User::factory()->create(['role' => UserRole::Personnel]);

    $makeIncident = function (string $trackingNumber) use ($type): Incident {
        return Incident::query()->create([
            'tracking_number' => $trackingNumber,
            'tracking_pin' => Hash::make('123456'),
            'incident_type_id' => $type->id,
            'status' => IncidentStatus::Assigned,
            'priority' => 'high',
            'description' => 'Dashboard notification feed test.',
            'is_anonymous' => true,
            'reported_at' => now(),
        ]);
    };
    $feed = app(DashboardAlertFeed::class);
    $adminCursor = $feed->cursorFor($admin);
    $agencyCursor = $feed->cursorFor($agencyUser);
    $personnelCursor = $feed->cursorFor($personnel);

    $adminIncident = $makeIncident('RAN-ALERT-ADMIN');
    $agencyIncident = $makeIncident('RAN-ALERT-AGENCY');
    $personnelIncident = $makeIncident('RAN-ALERT-PERSONNEL');
    Queue::fake();
    $notifications = app(NotificationService::class);
    $notifications->notifyAdminNewIncident($adminIncident);
    $agencyAssignment = Assignment::query()->create([
        'incident_id' => $agencyIncident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $admin->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);
    $personnelAssignment = Assignment::query()->create([
        'incident_id' => $personnelIncident->id,
        'assigned_to' => $personnel->id,
        'assigned_by' => $admin->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);
    $notifications->notifyAgencyAssigned($agencyAssignment);
    $notifications->notifyPersonnelAssigned($personnelAssignment);

    $adminNotification = Notification::query()->where('user_id', $admin->id)->firstOrFail();
    $otherAdminNotification = Notification::query()->where('user_id', $otherAdmin->id)->firstOrFail();
    $agencyNotification = Notification::query()->where('user_id', $agencyUser->id)->firstOrFail();
    $personnelNotification = Notification::query()->where('user_id', $personnel->id)->firstOrFail();

    $this->actingAs($admin)
        ->getJson(route('admin.dashboard.alerts', ['after' => $adminCursor]))
        ->assertOk()
        ->assertJsonPath('items.0.id', $adminNotification->id)
        ->assertJsonPath('items.0.incident_id', $adminIncident->id)
        ->assertJsonPath('items.0.type', 'incident.new')
        ->assertJsonMissing(['tracking_number' => $agencyIncident->tracking_number])
        ->assertJsonPath('cursor', $adminNotification->id);

    $this->actingAs($agencyUser)
        ->getJson(route('agency.dashboard.alerts', ['after' => $agencyCursor]))
        ->assertOk()
        ->assertJsonPath('items.0.id', $agencyNotification->id)
        ->assertJsonPath('items.0.incident_id', $agencyIncident->id)
        ->assertJsonPath('items.0.type', 'incident.assigned')
        ->assertJsonMissing(['tracking_number' => $personnelIncident->tracking_number])
        ->assertJsonPath('cursor', $agencyNotification->id);

    $this->actingAs($personnel)
        ->getJson(route('personnel.dashboard.alerts', ['after' => $personnelCursor]))
        ->assertOk()
        ->assertJsonPath('items.0.id', $personnelNotification->id)
        ->assertJsonPath('items.0.incident_id', $personnelIncident->id)
        ->assertJsonPath('items.0.type', 'incident.assigned')
        ->assertJsonMissing(['tracking_number' => $adminIncident->tracking_number])
        ->assertJsonPath('cursor', $personnelNotification->id);

    $this->actingAs($otherAdmin)
        ->getJson(route('admin.dashboard.alerts', ['after' => 0]))
        ->assertOk()
        ->assertJsonPath('items.0.incident_id', $adminIncident->id)
        ->assertJsonPath('items.0.id', $otherAdminNotification->id);
});

test('staff dashboards render the current account alert cursor and the notification toast', function () {
    $admin = User::factory()->administrator()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ALERT-CURSOR',
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'critical',
        'description' => 'Dashboard alert cursor render test.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Notification::query()->create([
        'user_id' => $admin->id,
        'incident_id' => $incident->id,
        'type' => 'incident.new',
        'title' => 'New incident reported',
        'message' => 'Dashboard alert cursor test.',
        'channel' => 'database',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('id="incident-alert-banner"', false)
        ->assertSee('id="incident-alert-title"', false)
        ->assertSee('id="incident-alert-backlog"', false)
        ->assertSee('Live command alert', false)
        ->assertDontSee('incident-alert-sound', false)
        ->assertDontSee('bi-volume-up-fill', false)
        ->assertSee('incident-toast-sweep', false)
        ->assertSee('incident-alert-ring', false)
        ->assertSee('setInterval(remindIncidentAlert, 30000)', false)
        ->assertDontSee('playAlertTone', false)
        ->assertDontSee('RANIAGAlertSound?.play()', false)
        ->assertSee('acknowledgeIncidentAlert(false)', false)
        ->assertDontSee('setTimeout(hideIncidentAlert', false)
        ->assertSee('let alertCursor = Number(1)', false)
        ->assertSee('dashboard/alerts.json', false)
        ->assertSee('loadAlertFeed()', false)
        ->assertSee('setInterval(loadAlertFeed, 10000)', false);
});

test('dashboard alert feed replays unread alerts after the initial cursor until acknowledged', function () {
    $admin = User::factory()->administrator()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ALERT-UNREAD',
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'critical',
        'description' => 'Unacknowledged alert should stay visible.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $notification = Notification::query()->create([
        'user_id' => $admin->id,
        'incident_id' => $incident->id,
        'type' => 'incident.new',
        'title' => 'New incident reported',
        'message' => 'Still awaiting review.',
        'channel' => 'database',
    ]);
    $feed = app(DashboardAlertFeed::class);
    $cursor = $feed->cursorFor($admin);

    $this->actingAs($admin)
        ->getJson(route('admin.dashboard.alerts', ['after' => $cursor]))
        ->assertOk()
        ->assertJsonPath('pending.0.id', $notification->id)
        ->assertJsonPath('pending_count', 1);

    $this->postJson(route('notifications.mark_read', $notification))
        ->assertOk();

    $this->getJson(route('admin.dashboard.alerts', ['after' => $cursor]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonCount(0, 'pending')
        ->assertJsonPath('pending_count', 0);
});

test('dashboard alert polling pages through bursts without dropping notifications', function () {
    $admin = User::factory()->administrator()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ALERT-BURST',
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'high',
        'description' => 'Dashboard burst pagination test.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    foreach (range(1, 12) as $index) {
        Notification::query()->create([
            'user_id' => $admin->id,
            'incident_id' => $incident->id,
            'type' => 'incident.new',
            'title' => 'New incident reported',
            'message' => "Burst notification {$index}.",
            'channel' => 'database',
        ]);
    }

    $firstPage = $this->actingAs($admin)
        ->getJson(route('admin.dashboard.alerts', ['after' => 0]))
        ->assertOk()
        ->assertJsonCount(10, 'items');
    $cursor = $firstPage->json('cursor');

    expect($cursor)->toBe($firstPage->json('items.9.id'));

    $this->getJson(route('admin.dashboard.alerts', ['after' => $cursor]))
        ->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.id', $cursor + 1);
});
