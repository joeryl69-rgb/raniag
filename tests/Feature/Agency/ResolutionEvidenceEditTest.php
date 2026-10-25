<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Evidence;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Resolution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

function resolutionEditFixture(): array
{
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'MDRRMO Pamplona',
        'code' => 'MDR'.uniqid(),
        'is_active' => true,
    ]);
    $user = User::factory()->create([
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-'.strtoupper(substr(uniqid(), -6)),
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'medium',
        'description' => 'Response still waiting on another office.',
        'latitude' => 18.47,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $user->id,
        'is_active' => false,
        'assigned_at' => now(),
    ]);
    $resolution = Resolution::query()->create([
        'incident_id' => $incident->id,
        'resolved_by' => $user->id,
        'summary' => 'The crew cleared the blocked road and checked the nearby houses.',
        'actions_taken' => 'Barricades were set and the resident was moved to a safer spot.',
        'resolved_at' => now(),
    ]);

    return [$user, $incident, $resolution];
}

test('edit my resolution shows the saved gps photo and can remove it', function () {
    [$user, $incident, $resolution] = resolutionEditFixture();
    $saved = Evidence::query()->create([
        'incident_id' => $incident->id,
        'uploaded_by' => $user->id,
        'type' => 'photo',
        'file_path' => 'incidents/'.$incident->id.'/evidence/saved-gps.jpg',
        'original_filename' => 'resolution-gps.jpg',
        'mime_type' => 'image/jpeg',
        'is_gps_capture' => true,
    ]);
    $reporter = Evidence::query()->create([
        'incident_id' => $incident->id,
        'uploaded_by' => null,
        'type' => 'photo',
        'file_path' => 'incidents/'.$incident->id.'/evidence/reporter-gps.jpg',
        'original_filename' => 'reporter-gps.jpg',
        'mime_type' => 'image/jpeg',
        'is_gps_capture' => true,
    ]);

    $this->actingAs($user)
        ->get(route('agency.incidents.show', $incident))
        ->assertOk()
        ->assertSee('GPS photos and files already submitted')
        ->assertSee('resolution-gps.jpg')
        ->assertSee('Remove')
        ->assertSee(route('agency.incidents.resolution.evidence.destroy', [$incident, $resolution, $saved]), false)
        ->assertDontSee(route('agency.incidents.resolution.evidence.destroy', [$incident, $resolution, $reporter]), false);

    $this->delete(route('agency.incidents.resolution.evidence.destroy', [$incident, $resolution, $saved]))
        ->assertRedirect(route('agency.incidents.show', $incident));

    expect(Evidence::query()->find($saved->id))->toBeNull()
        ->and(Evidence::query()->find($reporter->id))->not->toBeNull();

    $this->delete(route('agency.incidents.resolution.evidence.destroy', [$incident, $resolution, $reporter]))
        ->assertForbidden();
});

test('updating a resolution keeps the gps camera metadata on a new photo', function () {
    [$user, $incident, $resolution] = resolutionEditFixture();
    $photo = UploadedFile::fake()->image('capture.jpg');

    $this->actingAs($user)
        ->put(route('agency.incidents.resolution.update', [$incident, $resolution]), [
            'summary' => 'The crew cleared the blocked road and checked the nearby houses.',
            'actions_taken' => 'Barricades were set and the resident was moved to a safer spot.',
            'evidence' => [$photo],
            'meta' => [
                'gps_captures' => json_encode([[
                    'filename' => 'capture.jpg',
                    'latitude' => 18.4712,
                    'longitude' => 121.3411,
                    'location_source' => 'gps',
                    'captured_at' => now()->toIso8601String(),
                ]]),
            ],
        ])
        ->assertRedirect(route('agency.incidents.show', $incident));

    $evidence = Evidence::query()->where('incident_id', $incident->id)->first();
    expect($evidence)->not->toBeNull()
        ->and($evidence->is_gps_capture)->toBeTrue()
        ->and($evidence->uploaded_by)->toBe($user->id);
});
