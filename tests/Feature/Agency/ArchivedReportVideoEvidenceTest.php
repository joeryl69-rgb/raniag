<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Evidence;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('agency archived report launches uploaded video evidence without a duplicate inline preview', function () {
    $agency = Agency::query()->create([
        'name' => 'Pamplona Response',
        'code' => 'PRSP',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->create([
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $administrator = User::factory()->administrator()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ARCHIVE-VIDEO',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => IncidentStatus::Resolved,
        'priority' => 'medium',
        'description' => 'Archived video evidence test.',
        'barangay' => 'Bidduang',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'is_active' => false,
        'assigned_at' => now(),
    ]);
    Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'video',
        'file_path' => 'evidence/archive-test.webm',
        'original_filename' => 'gps-report.webm',
        'mime_type' => 'video/webm',
        'file_size' => 1024,
        'is_gps_capture' => true,
    ]);

    $this->actingAs($agencyUser)
        ->get(route('agency.archived_reports.show', $incident))
        ->assertOk()
        ->assertDontSee('<video', false)
        ->assertSee('rg-evidence-video-player', false)
        ->assertSee('rg-evidence-video-trigger', false)
        ->assertSee('data-rg-evidence-video', false)
        ->assertSee('js/evidence-video-player.js', false)
        ->assertSee('gps-report.webm');
});
