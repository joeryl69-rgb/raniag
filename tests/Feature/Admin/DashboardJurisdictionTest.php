<?php

use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use App\Services\SituationalMapService;
use Illuminate\Support\Facades\Hash;

test('admin dashboard separates same-named open reports by jurisdiction and omits closed status', function () {
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();

    foreach ([
        ['RAN-DASH-INSIDE', 'submitted', true],
        ['RAN-DASH-OUTSIDE', 'submitted', false],
        ['RAN-DASH-CLOSED', 'closed', true],
    ] as [$tracking, $status, $withinJurisdiction]) {
        Incident::query()->create([
            'tracking_number' => $tracking,
            'tracking_pin' => Hash::make('123456'),
            'incident_type_id' => $type->id,
            'status' => $status,
            'priority' => 'medium',
            'description' => 'Dashboard jurisdiction test.',
            'barangay' => 'Langagan',
            'is_anonymous' => true,
            'meta' => ['within_jurisdiction' => $withinJurisdiction],
            'reported_at' => now(),
        ]);
    }

    $points = app(SituationalMapService::class)
        ->openIncidentsPayload()
        ->keyBy('tracking_number');
    expect($points->keys()->all())
        ->toContain('RAN-DASH-INSIDE', 'RAN-DASH-OUTSIDE')
        ->not->toContain('RAN-DASH-CLOSED')
        ->and($points['RAN-DASH-INSIDE']['within_jurisdiction'])->toBeTrue()
        ->and($points['RAN-DASH-OUTSIDE']['within_jurisdiction'])->toBeFalse();

    $this->actingAs($administrator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee("const statusOrder = ['submitted', 'in_progress', 'resolved'];", false)
        ->assertSee("Inside' : 'Outside", false)
        ->assertSee("inside ? 'inside' : 'outside'", false)
        ->assertDontSee("closed: 'Closed'", false);
});
