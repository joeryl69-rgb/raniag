<?php

use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use App\Models\ReportArchive;
use App\Models\User;

test('an operations file downloads and stays in saved files', function () {
    $admin = User::factory()->administrator()->create();
    $center = EvacuationCenter::query()->create([
        'name' => 'Pamplona Municipal Gymnasium',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'is_open' => true,
        'capacity' => 80,
    ]);
    Evacuee::query()->create([
        'evacuation_center_id' => $center->id,
        'full_name' => 'Maria Cruz',
        'origin_scope' => 'outside',
        'origin_place' => 'Abulug',
        'barangay' => 'Centro',
        'checked_in_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.reports.operations'), [
            'report_kind' => 'shelter',
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ])
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $copy = ReportArchive::query()->first();
    expect($copy)->not->toBeNull()
        ->and($copy->kind)->toBe('shelter')
        ->and($copy->title)->toBe('Shelter guests');

    $this->actingAs($admin)
        ->get(route('admin.reports.copy', $copy))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('Gathered reports', false)
        ->assertDontSee('Saved reports', false)
        ->assertDontSee('Previously generated exports', false)
        ->assertDontSee('Build a report', false);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['view' => 'archive']))
        ->assertRedirect(route('admin.reports.index'));

    $this->actingAs($admin)
        ->post(route('admin.reports.operations'), [
            'report_kind' => 'daily',
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'aor_scope' => 'aor_only',
        ])
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
