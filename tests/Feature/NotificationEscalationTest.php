<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
});

test('notifyRole fans out database notifications to active admins', function () {
    $type = IncidentType::factory()->create();
    $adminA = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $adminB = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => false,
        'email_verified_at' => now(),
    ]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-NFY-'.strtoupper(substr(uniqid(), -4)),
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'high',
        'description' => 'Notification fan-out test incident with enough detail.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    app(NotificationService::class)->notifyRole(
        UserRole::Administrator,
        'incident.test',
        'Test title',
        'Test message body',
        $incident,
    );

    expect(Notification::query()->where('type', 'incident.test')->count())->toBe(2)
        ->and(Notification::query()->where('user_id', $adminA->id)->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $adminB->id)->exists())->toBeTrue();
});

test('raniag:escalate-sla notifies once and stamps meta', function () {
    $type = IncidentType::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-SLA-'.strtoupper(substr(uniqid(), -4)),
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'high',
        'description' => 'SLA escalation test incident with enough detail.',
        'is_anonymous' => true,
        'reported_at' => now()->subHours(((int) config('raniag.sla_target_hours', 48)) + 2),
        'meta' => [],
    ]);

    $this->artisan('raniag:escalate-sla')->assertSuccessful();

    $incident->refresh();
    expect(data_get($incident->meta, 'sla_escalated_at'))->not->toBeNull()
        ->and(Notification::query()->where('user_id', $admin->id)->where('type', 'incident.sla_breach')->exists())->toBeTrue();

    $before = Notification::query()->where('type', 'incident.sla_breach')->count();
    $this->artisan('raniag:escalate-sla')->assertSuccessful();
    expect(Notification::query()->where('type', 'incident.sla_breach')->count())->toBe($before);
});
