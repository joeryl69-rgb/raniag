<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;

test('agency and personnel pending-info updates preserve the investigation log for staff only', function () {
    $administrator = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'Pamplona Response Agency',
        'code' => 'PRA1',
        'is_active' => true,
    ]);
    $type = IncidentType::factory()->create();

    foreach ([
        ['role' => UserRole::Agency, 'tracking' => 'RAN-INFO-AGENCY'],
        ['role' => UserRole::Personnel, 'tracking' => 'RAN-INFO-PERSON'],
    ] as $case) {
        $user = User::factory()->create([
            'role' => $case['role'],
            'agency_id' => $case['role'] === UserRole::Agency ? $agency->id : null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $incident = Incident::query()->create([
            'tracking_number' => $case['tracking'],
            'incident_type_id' => $type->id,
            'status' => IncidentStatus::InProgress,
            'priority' => 'medium',
            'description' => 'Pending information update test.',
            'is_anonymous' => true,
            'reported_at' => now(),
        ]);
        Assignment::query()->create([
            'incident_id' => $incident->id,
            'agency_id' => $case['role'] === UserRole::Agency ? $agency->id : null,
            'assigned_to' => $case['role'] === UserRole::Personnel ? $user->id : null,
            'assigned_by' => $administrator->id,
            'is_active' => true,
            'acknowledged_at' => now(),
            'assigned_at' => now(),
        ]);

        $adminCaseUrl = route('admin.incidents.show', $incident);
        $this->actingAs($administrator)
            ->get($adminCaseUrl)
            ->assertOk()
            ->assertSee('id="incident-response-status" data-live-refresh', false)
            ->assertDontSee('Please confirm the exact incident location.');

        $this->actingAs($user)
            ->patch(route($case['role'] === UserRole::Agency
                ? 'agency.incidents.update_status'
                : 'personnel.incidents.update_status', $incident), [
                'status' => IncidentStatus::PendingInfo->value,
                'needs_info' => 'Please confirm the exact incident location.',
                'comment' => 'Coordinates do not match the scene report.',
            ])
            ->assertRedirect();

        $update = $incident->statusUpdates()->latest('id')->firstOrFail();
        expect($update->comment)
            ->toContain('Awaiting information: Please confirm the exact incident location.')
            ->toContain('Investigation log: Coordinates do not match the scene report.')
            ->and($update->is_public)->toBeFalse();

        $this->actingAs($user)
            ->get(route($case['role'] === UserRole::Agency
                ? 'agency.incidents.show'
                : 'personnel.incidents.show', $incident))
            ->assertOk()
                ->assertSee('toggleInfoView();', false)
                ->assertSee('Requested details:')
                ->assertSee('Investigation log:');

        $this->actingAs($administrator)
            ->get($adminCaseUrl.'?_live_refresh='.now()->timestamp)
            ->assertOk()
            ->assertSee('id="incident-response-status" data-live-refresh', false)
            ->assertSee('Please confirm the exact incident location.')
            ->assertSee('Coordinates do not match the scene report.')
            ->assertSee('Requested details:')
            ->assertSee('Investigation log:');

        $this->withSession(['track_verified.'.$incident->id => true])
            ->get(route('public.track.case', $incident->tracking_number))
            ->assertOk()
            ->assertDontSee('Please confirm the exact incident location.')
            ->assertDontSee('Coordinates do not match the scene report.');
    }
});
