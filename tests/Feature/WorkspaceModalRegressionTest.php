<?php

use App\Models\User;

test('the general report QR lives on the homepage and the admin poster page is gone', function () {
    $administrator = User::factory()->administrator()->create();

    expect(\Illuminate\Support\Facades\Route::has('admin.qr_posters.index'))->toBeFalse();

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('id="home-report-qr"', false)
        ->assertSee('data-report-qr-download="home-report-qr"', false)
        ->assertSee('Download QR image')
        ->assertSee(route('public.report.create'), false);

    $this->actingAs($administrator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Report QR', false)
        ->assertDontSee('QR Posters', false);
});

test('public support confirmation is portaled and restores page state on dismissal', function () {
    $this->get(route('public.support'))
        ->assertOk()
        ->assertSee('js/modal-root.js', false)
        ->assertSee('id="supportConfirmBackdrop"', false)
        ->assertSee('document.body.appendChild(backdrop)', false)
        ->assertSee("body.style.position = 'fixed'", false)
        ->assertSee("if (e.key === 'Escape')", false)
        ->assertSee('e.target === backdrop', false)
        ->assertSee('returnFocus.focus()', false)
        ->assertSee('window.scrollTo(0, savedScrollY)', false)
        ->assertSee('savedStyles.bodyPosition', false);
});

test('staff modals use the shared root manager and long workspace dialogs scroll', function () {
    $administrator = User::factory()->administrator()->create();

    $this->actingAs($administrator)
        ->get(route('admin.incident_types.index'))
        ->assertOk()
        ->assertSee('js/modal-root.js', false)
        ->assertSee('js/table-view-mode.js', false)
        ->assertDontSee('data-view-mode-root="incident-types"', false)
        ->assertDontSee('data-view-mode-choice="cards"', false)
        ->assertSee('id="typeModal"', false)
        ->assertSee('modal-dialog-centered modal-dialog-scrollable', false);

    $this->get(route('admin.announcements.index'))
        ->assertOk()
        ->assertSee('id="annModal"', false)
        ->assertSee('modal-dialog-centered modal-dialog-scrollable', false)
        ->assertSee('rg-announcements-list', false);
});
