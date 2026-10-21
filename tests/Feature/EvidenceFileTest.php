<?php

use App\Models\Evidence;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use App\Services\EvidenceService;
use App\Support\PrivateIncidentFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('gps watermark place labels include barangay municipality province and country without inventing a barangay', function () {
    $service = app(EvidenceService::class);
    $resolveAddress = new ReflectionMethod($service, 'resolveFullAddress');

    expect($resolveAddress->invoke($service, 'Langagan', 'Sanchez Mira'))
        ->toBe('Barangay Langagan, Sanchez Mira, Cagayan, Philippines')
        ->and($resolveAddress->invoke($service, null, 'Sanchez Mira'))
        ->toBe('Barangay not resolved, Sanchez Mira, Cagayan, Philippines');
});

test('gps watermark map crop keeps a fix near a tile edge centered', function () {
    $service = app(EvidenceService::class);
    $centeredMapCrop = new ReflectionMethod($service, 'centeredMapCrop');
    $centerTile = imagecreatetruecolor(256, 256);
    $eastTile = imagecreatetruecolor(256, 256);
    $blue = imagecolorallocate($centerTile, 0, 0, 255);
    $red = imagecolorallocate($eastTile, 255, 0, 0);
    imagefill($centerTile, 0, 0, $blue);
    imagefill($eastTile, 0, 0, $red);

    $crop = $centeredMapCrop->invoke($service, [
        '0:0' => $centerTile,
        '1:0' => $eastTile,
    ], 0.95, 0.5);

    expect($crop)->toBeInstanceOf(GdImage::class)
        ->and(imagecolorat($crop, 128, 128) & 0xFFFFFF)->toBe(0x0000FF)
        ->and(imagecolorat($crop, 200, 128) & 0xFFFFFF)->toBe(0xFF0000);

    imagedestroy($crop);
    imagedestroy($centerTile);
    imagedestroy($eastTile);
});

test('gps watermark accepts JPEG satellite tiles', function () {
    config(['raniag.map.mapbox_token' => 'test-mapbox-token']);

    $tileImage = imagecreatetruecolor(2, 2);
    ob_start();
    imagejpeg($tileImage, null, 80);
    $tileBytes = ob_get_clean();
    imagedestroy($tileImage);

    Http::fake([
        'https://api.mapbox.com/*' => Http::response($tileBytes, 200, ['Content-Type' => 'image/jpeg']),
    ]);

    do {
        $latitude = random_int(-6000, 6000) / 100;
        $longitude = random_int(-17000, 17000) / 100;
        $zoom = 17;
        $tileCount = 2 ** $zoom;
        $xTile = (int) floor((($longitude + 180) / 360) * $tileCount);
        $latitudeRadians = deg2rad($latitude);
        $yTile = (int) floor(((1 - log(tan($latitudeRadians) + 1 / cos($latitudeRadians)) / M_PI) / 2) * $tileCount);
        $cachePaths = [];

        for ($dy = -1; $dy <= 1; $dy++) {
            for ($dx = -1; $dx <= 1; $dx++) {
                $tileX = (($xTile + $dx) % $tileCount + $tileCount) % $tileCount;
                $tileY = $yTile + $dy;
                if ($tileY >= 0 && $tileY < $tileCount) {
                    $cachePaths[] = storage_path("app/map-tiles/satellite-streets-v12/{$zoom}/{$tileX}/{$tileY}.jpg");
                }
            }
        }
    } while (collect($cachePaths)->contains(fn (string $path) => is_file($path)));

    $map = null;
    try {
        $fetchMapTile = new ReflectionMethod(app(EvidenceService::class), 'fetchMapTile');
        $map = $fetchMapTile->invoke(app(EvidenceService::class), $latitude, $longitude, $zoom);

        expect($map)->toBeArray()
            ->and($map['image'] ?? null)->toBeInstanceOf(GdImage::class);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.mapbox.com')
            && str_contains($request->url(), 'satellite-streets-v12'));
    } finally {
        if (isset($map['image']) && $map['image'] instanceof GdImage) {
            imagedestroy($map['image']);
        }

        foreach ($cachePaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
});

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

test('video evidence supports byte range requests for playback seeking', function () {
    Storage::fake('local');

    $admin = User::factory()->create([
        'role' => 'administrator',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-TEST-VIDEO',
        'tracking_pin' => Hash::make('135790'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => 'submitted',
        'priority' => 'medium',
        'description' => 'Video range request test.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $path = 'incidents/'.$incident->id.'/evidence/range-test.webm';
    $videoBytes = str_repeat('0123456789', 100);
    Storage::disk(PrivateIncidentFiles::DISK)->put($path, $videoBytes);

    $evidence = Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'video',
        'file_path' => $path,
        'original_filename' => 'range-test.webm',
        'mime_type' => 'video/webm',
        'file_size' => strlen($videoBytes),
        'is_gps_capture' => true,
    ]);

    $this->actingAs($admin)
        ->withHeaders(['Range' => 'bytes=100-199'])
        ->get(route('evidence.show', $evidence))
        ->assertStatus(206)
        ->assertHeader('Content-Range', 'bytes 100-199/1000');
});
