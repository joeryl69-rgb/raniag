<?php

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

test('removing a quick-login account revokes trusted device so otp is required again', function () {
    Mail::fake();
    config(['raniag.two_factor.enabled' => true]);

    $user = User::factory()->administrator()->create([
        'password' => bcrypt('secret123'),
    ]);

    $twoFactor = app(TwoFactorService::class);
    $trusted = $twoFactor->issueTrustedDeviceCookie($user);
    $recognized = $twoFactor->issueRecognizedUserCookie(Request::create('/'), $user);

    // Baseline: trusted cookie skips OTP.
    $this->withCookie($trusted->getName(), $trusted->getValue())
        ->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    // Remove from quick login while the trusted cookie is still present.
    $this->withCookie($trusted->getName(), $trusted->getValue())
        ->withCookie($recognized->getName(), $recognized->getValue())
        ->post(route('login.forget-device'), ['email' => $user->email])
        ->assertRedirect(route('login'))
        ->assertCookieExpired(TwoFactorService::TRUSTED_COOKIE);

    // Test client's withCookie() sticks across requests — overwrite with an
    // empty trust jar to mirror the browser accepting Set-Cookie::forget.
    $this->withCookie(TwoFactorService::TRUSTED_COOKIE, '[]');

    Mail::fake();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])
        ->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();
    expect(session('pending_2fa_id'))->toBe($user->id);
    Mail::assertSent(\App\Mail\LoginOtpMail::class);
});

test('back to sign in clears pending two factor challenge', function () {
    $user = User::factory()->administrator()->create();

    session(['pending_2fa_id' => $user->id]);

    $this->post(route('two-factor.cancel'))
        ->assertRedirect(route('login'));

    expect(session()->has('pending_2fa_id'))->toBeFalse();
});

test('forget trusted device clears hasTrustedDevice for that user', function () {
    $user = User::factory()->administrator()->create();
    $twoFactor = app(TwoFactorService::class);
    $trusted = $twoFactor->issueTrustedDeviceCookie($user);

    $withTrust = Request::create('/');
    $withTrust->cookies->set($trusted->getName(), $trusted->getValue());

    expect($twoFactor->hasTrustedDevice($withTrust, $user))->toBeTrue();

    $forget = $twoFactor->forgetTrustedDevice($withTrust, $user);

    $after = Request::create('/');
    if ($forget->getValue() !== null && $forget->getValue() !== '') {
        $after->cookies->set($forget->getName(), $forget->getValue());
    }

    expect($twoFactor->hasTrustedDevice($after, $user))->toBeFalse();
});
