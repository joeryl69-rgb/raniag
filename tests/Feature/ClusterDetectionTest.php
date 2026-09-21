<?php

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Services\IncidentService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    $this->type = IncidentType::factory()->create();
});

test('nearby same-type reports are linked as a corroborating cluster', function () {
    $existing = Incident::query()->create([
        'tracking_number' => 'RAN-CLU-AAAA',
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => $this->type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'Existing nearby incident description for cluster test.',
        'latitude' => 18.47200000,
        'longitude' => 121.32500000,
        'is_anonymous' => true,
        'reported_at' => now()->subMinutes(10),
        'meta' => [],
    ]);

    $incident = app(IncidentService::class)->submitAnonymousReport([
        'incident_type_id' => $this->type->id,
        'description' => 'New nearby incident description for cluster detection.',
        'latitude' => 18.47220000,
        'longitude' => 121.32510000,
        'is_anonymous' => true,
        'priority' => 'medium',
    ]);

    $incident->refresh();
    $existing->refresh();

    expect(data_get($incident->meta, 'redundancy_signal'))->toBeTrue()
        ->and(data_get($incident->meta, 'cluster.linked_incident_ids'))->toContain($existing->id)
        ->and(data_get($existing->meta, 'cluster.linked_incident_ids'))->toContain($incident->id);
});
