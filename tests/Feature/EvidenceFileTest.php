<?php

use App\Models\Evidence;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use App\Support\PrivateIncidentFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('guests cannot view private evidence files', function () {
    Storage::fake('local');

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-TEST-0001',
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => 'submitted',
        'priority' => 'medium',
        'description' => 'Evidence privacy check incident.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $path = 'incidents/'.$incident->id.'/evidence/sample.jpg';
    Storage::disk(PrivateIncidentFiles::DISK)->put($path, 'fake-image-bytes');

    $evidence = Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'photo',
        'file_path' => $path,
        'original_filename' => 'sample.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 16,
        'priority' => 1,
        'is_gps_capture' => true,
    ]);

    $this->get(route('evidence.show', $evidence))->assertRedirect(route('login'));
});

test('administrators can view private evidence files', function () {
    Storage::fake('local');

    $admin = User::factory()->create([
        'role' => 'administrator',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-TEST-0002',
        'tracking_pin' => Hash::make('654321'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => 'submitted',
        'priority' => 'medium',
        'description' => 'Evidence privacy check incident for admin.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $path = 'incidents/'.$incident->id.'/evidence/admin-sample.jpg';
    Storage::disk(PrivateIncidentFiles::DISK)->put($path, 'fake-image-bytes');

    $evidence = Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'photo',
        'file_path' => $path,
        'original_filename' => 'admin-sample.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 16,
        'priority' => 1,
        'is_gps_capture' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('evidence.show', $evidence))
        ->assertOk();
});
