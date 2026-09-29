<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('situational map refresh bypasses the cached dashboard feed', function () {
    $admin = User::factory()->administrator()->create();

    Cache::put('admin.dashboard.json', [
        'total_incidents' => 4242,
        'recent_incidents' => [],
    ], 25);

    $this->actingAs($admin)
        ->getJson(route('admin.dashboard.api'))
        ->assertOk()
        ->assertJsonPath('total_incidents', 4242);

    $this->actingAs($admin)
        ->getJson(route('admin.dashboard.api', ['fresh' => 1]));

    expect(data_get(Cache::get('admin.dashboard.json'), 'total_incidents'))->not->toBe(4242);
});

test('every staff dashboard wires the situational map refresh button', function (string $role, string $route) {
    $user = in_array($role, ['administrator', 'agency'], true)
        ? User::factory()->{$role}()->create()
        : User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route($route))
        ->assertOk()
        ->assertSee('id="map-refresh-btn"', false)
        ->assertSee('loadData({ manual: true })', false)
        ->assertSee('fresh=1', false);
})->with([
    'administrator' => ['administrator', 'admin.dashboard'],
    'agency' => ['agency', 'agency.dashboard'],
    'personnel' => ['personnel', 'personnel.dashboard'],
]);
