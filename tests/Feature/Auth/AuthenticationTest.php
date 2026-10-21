<?php

use App\Models\User;
use App\Mail\LoginOtpMail;
use Illuminate\Support\Facades\Mail;

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

test('administrator login sends an email verification code', function () {
    Mail::fake();
    $user = User::factory()->administrator()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertGuest();
    $response->assertRedirect(route('two-factor.challenge', absolute: false));
    Mail::assertSent(LoginOtpMail::class, fn (LoginOtpMail $mail) => $mail->hasTo($user->email));
});

test('agency login sends an email verification code', function () {
    Mail::fake();
    $user = User::factory()->agency()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertGuest();
    $response->assertRedirect(route('two-factor.challenge', absolute: false));
    Mail::assertSent(LoginOtpMail::class, fn (LoginOtpMail $mail) => $mail->hasTo($user->email));
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
