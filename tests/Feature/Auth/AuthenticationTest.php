<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('login screen shows the updated sign-in experience', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSee('Sign in to your account')
        ->assertSee('Show password');
});

test('administrators are redirected to the admin dashboard after login', function () {
    $user = User::factory()->administrator()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('agency users are redirected to the agency dashboard after login', function () {
    $user = User::factory()->agency()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('agency.dashboard', absolute: false));
});

test('seeders create the baseline dashboard data', function () {
    $this->artisan('db:seed')->assertSuccessful();

    expect(DB::table('incident_types')->count())->toBeGreaterThan(0)
        ->and(DB::table('agencies')->count())->toBeGreaterThan(0)
        ->and(DB::table('users')->count())->toBeGreaterThan(0);
});

test('inactive users cannot authenticate', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
