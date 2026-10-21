<?php

use Illuminate\Support\Facades\Http;

test('satellite map tile proxy preserves Mapbox JPEG tiles', function () {
    config(['raniag.map.mapbox_token' => 'test-mapbox-token']);

    do {
        $x = random_int(0, 131071);
        $y = random_int(0, 131071);
        $path = storage_path("app/map-tiles/satellite-streets-v12/17/{$x}/{$y}.jpg");
    } while (is_file($path));

    $tileBytes = "\xFF\xD8\xFFmapbox-test-tile";
    Http::fake([
        'https://api.mapbox.com/*' => Http::response($tileBytes, 200, ['Content-Type' => 'image/jpeg']),
    ]);

    try {
        $response = $this->get("/map-tile/17/{$x}/{$y}");

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        expect($response->getContent())->toBe($tileBytes);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'satellite-streets-v12'));
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});
