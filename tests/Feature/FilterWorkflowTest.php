<?php

use App\Models\User;

test('audit and incident filters use explicit apply and consistent active-filter indicators', function () {
    $administrator = User::factory()->administrator()->create();

    $this->actingAs($administrator)
        ->get(route('admin.audit-logs', [
            'q' => 'dispatch',
            'log_name' => 'all',
            'sort' => 'created_at',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertSee('data-auto-submit="false"', false)
        ->assertSee('Apply filters')
        ->assertSee('Clear all')
        ->assertSee('class="rg-filter-menu-count"', false)
        ->assertSee('class="rg-filter-chips"', false)
        ->assertSee('name="q"', false)
        ->assertSee('value="dispatch"', false)
        ->assertSee('sort=created_at');

    $this->get(route('admin.incidents.index', [
        'q' => 'dispatch',
        'status' => 'submitted',
        'sort' => 'reported_at',
        'direction' => 'asc',
    ]))
        ->assertOk()
        ->assertSee('data-auto-submit="false"', false)
        ->assertSee('Apply filters')
        ->assertSee('Clear all')
        ->assertSee('class="rg-filter-menu-count"', false)
        ->assertSee('class="rg-filter-chips"', false)
        ->assertSee('data-filter-ignore', false)
        ->assertSee('value="dispatch"', false);
});
