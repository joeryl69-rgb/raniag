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
        ->assertSee('data-view-mode-root="evacuee-registry"', false)
        ->assertSee('id="evacuee-search"', false)
        ->assertSee('id="evac-origin-filter"', false)
        ->assertSee('id="evac-stay-filter"', false)
        ->assertSee('data-stay="inside"', false)
        ->assertSee('Still inside', false)
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
