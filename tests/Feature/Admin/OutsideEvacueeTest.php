<?php

use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use App\Models\User;

test('hazard counters show change and an outside evacuee stays distinct', function () {
    $admin = User::factory()->administrator()->create();
    $center = EvacuationCenter::query()->create([
        'name' => 'Pamplona Municipal Gymnasium',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'is_open' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.hazard.index'))
        ->assertOk()
        ->assertSee('vs last month', false)
        ->assertSee('Outside the municipality', false)
        ->assertSee('0 checked in', false)
        ->assertDontSee('data-view-mode-root="evacuee-registry"', false)
        ->assertDontSee('Table view selected');

    $this->actingAs($admin)
        ->post(route('admin.hazard.evacuees.store'), [
            'evacuation_center_id' => $center->id,
            'full_name' => 'Maria Cruz',
            'origin_scope' => 'outside',
            'origin_place' => 'Abulug',
            'barangay' => 'Centro',
            'age' => 34,
            'sex' => 'Female',
        ])
        ->assertRedirect(route('admin.hazard.index', ['tab' => 'registry']));

    $person = Evacuee::query()->where('full_name', 'Maria Cruz')->first();
    expect($person)->not->toBeNull()
        ->and($person->origin_scope)->toBe('outside')
        ->and($person->homeLabel())->toBe('Centro, Abulug');

    $this->actingAs($admin)
        ->get(route('admin.hazard.index', ['tab' => 'registry']))
        ->assertOk()
        ->assertSee('Outside municipality', false)
        ->assertSee('Centro, Abulug', false)
        ->assertDontSee('DEMO', false)
        ->assertSee('data-view-mode-root="evacuee-registry"', false)
        ->assertSee('id="evacuee-search"', false)
        ->assertSee('id="evac-origin-filter"', false)
        ->assertSee('id="evac-stay-filter"', false)
        ->assertSee('data-stay="inside"', false)
        ->assertSee('Still inside', false)
        ->assertSee('name="stay"', false)
        ->assertSee('name="origin"', false)
        ->assertSee('data-origin="all"', false)
        ->assertSee('data-origin="inside"', false)
        ->assertSee('data-origin="outside"', false);

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('Gathered reports', false)
        ->assertSee('Evacuee registry', false)
        ->assertDontSee('Saved reports', false);

    $this->get(route('admin.reports.quick', ['period' => 'week']))
        ->assertOk()
        ->assertSee('People sheltered now', false)
        ->assertSee('1 from outside', false);
});

test('check-in is blocked when a shelter is full and the registry can filter by shelter', function () {
    $admin = User::factory()->administrator()->create();
    $fullCenter = EvacuationCenter::query()->create([
        'name' => 'Centro Covered Court',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'capacity' => 1,
        'is_open' => true,
    ]);
    $otherCenter = EvacuationCenter::query()->create([
        'name' => 'Curva Elementary School',
        'latitude' => 18.46,
        'longitude' => 121.35,
        'capacity' => 5,
        'is_open' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.hazard.evacuees.store'), [
        'evacuation_center_id' => $fullCenter->id,
        'full_name' => 'Maria Cruz',
        'origin_scope' => 'inside',
        'barangay' => 'Centro',
    ])->assertRedirect(route('admin.hazard.index', ['tab' => 'registry']));

    $this->post(route('admin.hazard.evacuees.store'), [
        'evacuation_center_id' => $fullCenter->id,
        'full_name' => 'Juan Ramos',
        'origin_scope' => 'inside',
        'barangay' => 'Centro',
    ])->assertSessionHasErrors('evacuation_center_id');

    $response = $this->get(route('admin.hazard.index', [
        'tab' => 'registry',
        'evacuation_center_id' => $fullCenter->id,
    ]))->assertOk()
        ->assertSee('id="evacuee-center-filter"', false)
        ->assertSee('Centro Covered Court', false)
        ->assertDontSee('Curva Elementary School</option>', false);

    expect($response->viewData('evacuees')->pluck('full_name')->all())
        ->toBe(['Maria Cruz']);
});

test('demo evacuees vary by shelter capacity, stay within capacity, and seed only once', function () {
    $small = EvacuationCenter::query()->create([
        'name' => 'Small Shelter',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'capacity' => 12,
        'is_open' => true,
    ]);
    $large = EvacuationCenter::query()->create([
        'name' => 'Large Shelter',
        'latitude' => 18.46,
        'longitude' => 121.35,
        'capacity' => 100,
        'is_open' => true,
    ]);
    $closed = EvacuationCenter::query()->create([
        'name' => 'Closed Shelter',
        'latitude' => 18.47,
        'longitude' => 121.36,
        'capacity' => 40,
        'is_open' => false,
    ]);

    $seeder = app(\Database\Seeders\DemoEvacueeSeeder::class);
    $seeder->run();
    $smallCount = $small->evacuees()->where('is_demo', true)->count();
    $largeCount = $large->evacuees()->where('is_demo', true)->count();
    $seeder->run();

    expect($smallCount)->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(12)
        ->and($largeCount)->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(30)
        ->and($small->evacuees()->where('is_demo', true)->count())->toBe($smallCount)
        ->and($large->evacuees()->where('is_demo', true)->count())->toBe($largeCount)
        ->and($closed->evacuees()->count())->toBe(0)
        ->and($small->evacuees()->where('is_demo', true)->value('full_name'))->not->toStartWith('[DEMO]')
        ->and($small->evacuees()->where('is_demo', true)->where('is_vulnerable', true)->count())->toBe(0);
});

test('existing evacuee presentation labels are removed without changing names otherwise', function () {
    $center = EvacuationCenter::query()->create([
        'name' => 'Label Cleanup Shelter',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'is_open' => true,
    ]);
    $evacuee = Evacuee::query()->create([
        'evacuation_center_id' => $center->id,
        'full_name' => '[DEMO] Maria Santos',
        'is_demo' => true,
        'is_vulnerable' => true,
        'vulnerability_notes' => 'Demo record — verify before operational use',
        'origin_scope' => 'inside',
        'barangay' => 'Centro',
        'checked_in_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_11_08_103000_clean_evacuee_presentation_labels.php');
    $migration->up();
    $evacuee->refresh();

    expect($evacuee->full_name)->toBe('Maria Santos')
        ->and($evacuee->is_vulnerable)->toBeFalse()
        ->and($evacuee->vulnerability_notes)->toBeNull();
});

test('evacuee registry paginates and applies search and origin filters across records', function () {
    $admin = User::factory()->administrator()->create();
    $center = EvacuationCenter::query()->create([
        'name' => 'Pamplona Municipal Gymnasium',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'is_open' => true,
    ]);

    foreach (range(1, 32) as $number) {
        Evacuee::query()->create([
            'evacuation_center_id' => $center->id,
            'full_name' => 'Evacuee '.$number,
            'origin_scope' => $number === 32 ? 'outside' : 'inside',
            'origin_place' => $number === 32 ? 'Abulug' : null,
            'barangay' => 'Centro',
            'checked_in_at' => now(),
        ]);
    }

    $baseQuery = [
        'tab' => 'registry',
        'evacuation_center_id' => $center->id,
        'stay' => 'all',
    ];
    $firstPage = $this->actingAs($admin)
        ->get(route('admin.hazard.index', $baseQuery))
        ->assertOk()
        ->assertSee('Showing 1–15 of 32 evacuees', false)
        ->assertSee('page=2', false);
    expect($firstPage->viewData('evacuees')->total())->toBe(32)
        ->and($firstPage->viewData('evacuees')->perPage())->toBe(15);

    $secondPage = $this->get(route('admin.hazard.index', [...$baseQuery, 'page' => 2]))
        ->assertOk()
        ->assertSee('Evacuee 17', false);
    expect($secondPage->viewData('evacuees')->currentPage())->toBe(2)
        ->and($secondPage->viewData('evacuees')->total())->toBe(32);

    $filtered = $this->get(route('admin.hazard.index', [
        ...$baseQuery,
        'origin' => 'outside',
        'q' => 'Abulug',
    ]))->assertOk()->assertSee('Evacuee 32', false);
    expect($filtered->viewData('evacuees')->total())->toBe(1);
});
