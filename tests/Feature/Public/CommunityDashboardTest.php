<?php

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Support\Facades\Hash;

test('community dashboard only counts incidents inside Pamplona', function () {
    $type = IncidentType::factory()->create(['name' => 'Fire']);
    $base = [
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'high',
        'description' => 'Community dashboard scope.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ];

    Incident::query()->create($base + [
        'tracking_number' => 'RAN-COM-0001',
        'barangay' => 'Bidduang',
    ]);
    Incident::query()->create($base + [
        'tracking_number' => 'RAN-COM-0002',
        'barangay' => 'Langagan',
    ]);

    $response = $this->getJson(route('public.dashboard.data', ['period' => 'quarter']))
        ->assertOk()
        ->assertJsonPath('period.key', 'quarter')
        ->assertJsonPath('total_all_time', 1)
        ->assertJsonPath('most_active.barangay', 'Bidduang')
        ->assertJsonPath('most_active.type', 'Fire')
        ->assertJsonPath('barangay_counts.0.barangay', 'Bidduang');

    expect(collect($response->json('recent_activity'))->pluck('barangay'))
        ->not->toContain('Langagan');
});
