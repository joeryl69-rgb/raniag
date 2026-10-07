<?php

use App\Mail\LoginOtpMail;
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

test('successful otp verification can remember a device with database cache', function () {
    Mail::fake();
    config([
        'cache.default' => 'database',
        'raniag.two_factor.enabled' => true,
        'raniag.two_factor.trusted_device_days' => -1,
    ]);

    $user = User::factory()->administrator()->create([
        'password' => bcrypt('secret123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('two-factor.challenge'));

    $code = null;
    Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code) {
        $mail->assertSeeInHtml('src="data:image/png;base64,', false);
        $mail->assertSeeInHtml('alt="RANIAG"', false);
        $code = $mail->code;

        return true;
    });

    $verifiedResponse = $this->post(route('two-factor.store'), [
        'code' => $code,
        'remember_device' => '1',
    ])
        ->assertRedirect(route('admin.dashboard', absolute: false))
        ->assertCookie(TwoFactorService::TRUSTED_COOKIE)
        ->assertCookie(TwoFactorService::RECOGNIZED_COOKIE);

    $this->assertAuthenticatedAs($user);

    $trustedCookie = $verifiedResponse->getCookie(TwoFactorService::TRUSTED_COOKIE);
    $recognizedCookie = $verifiedResponse->getCookie(TwoFactorService::RECOGNIZED_COOKIE);

    $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
        ->withCookie($recognizedCookie->getName(), $recognizedCookie->getValue())
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();

    $this->get('/login')->assertOk()->assertSee('data-quick="1"', false);

    $this->post(route('login.quick'), ['email' => $user->email])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('successful otp verification does not remember a device when unchecked', function () {
    Mail::fake();
    config([
        'cache.default' => 'database',
        'raniag.two_factor.enabled' => true,
        'raniag.two_factor.trusted_device_days' => -1,
    ]);

    $user = User::factory()->administrator()->create([
        'password' => bcrypt('secret123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('two-factor.challenge'));

    $code = null;
    Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $this->post(route('two-factor.store'), [
        'code' => $code,
    ])
        ->assertRedirect(route('admin.dashboard', absolute: false))
        ->assertCookieExpired(TwoFactorService::TRUSTED_COOKIE)
        ->assertCookieExpired(TwoFactorService::RECOGNIZED_COOKIE);

    $this->assertAuthenticatedAs($user);
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('data-email="'.$user->email.'"', false);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('two-factor.challenge'));
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

test('a remembered device signs in from the account icon without a password', function () {
    config(['raniag.two_factor.enabled' => true]);

    $user = User::factory()->administrator()->create();
    $trusted = app(TwoFactorService::class)->issueTrustedDeviceCookie($user);

    $this->withCookie($trusted->getName(), $trusted->getValue())
        ->post(route('login.quick'), ['email' => $user->email])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('remembered accounts use the current profile name and photo', function () {
    $user = User::factory()->administrator()->create(['name' => 'Current Staff Name']);
    $user->avatar_path = 'avatars/current-staff.png';
    $user->save();

    $request = Request::create('/', 'GET', [], [
        TwoFactorService::RECOGNIZED_COOKIE => base64_encode(json_encode([
            ['name' => 'Old Staff Name', 'email' => $user->email],
        ])),
    ]);

    $account = app(TwoFactorService::class)->recognizedUsers($request)[0];

    expect($account['name'])->toBe('Current Staff Name')
        ->and($account['initials'])->toBe($user->initials)
        ->and($account['avatar_url'])->toBe($user->avatar_url);
});

test('agency and personnel are sent to the email code, and removing them requires it again', function (string $role, string $home) {
    Mail::fake();
    config(['raniag.two_factor.enabled' => true]);

    $user = User::factory()->create([
        'role' => $role,
        'password' => bcrypt('secret123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();

    $twoFactor = app(TwoFactorService::class);
    $trusted = $twoFactor->issueTrustedDeviceCookie($user);
    $recognized = $twoFactor->issueRecognizedUserCookie(\Illuminate\Http\Request::create('/'), $user);

    $this->withCookie($trusted->getName(), $trusted->getValue())
        ->post(route('login.quick'), ['email' => $user->email])
        ->assertRedirect(route($home, absolute: false));

    $this->post('/logout');

    $this->withCookie($trusted->getName(), $trusted->getValue())
        ->withCookie($recognized->getName(), $recognized->getValue())
        ->post(route('login.forget-device'), ['email' => $user->email])
        ->assertCookieExpired(TwoFactorService::TRUSTED_COOKIE);

    $this->withCookie(TwoFactorService::TRUSTED_COOKIE, '[]');
    Mail::fake();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('two-factor.challenge'));
})->with([
    ['agency', 'agency.dashboard'],
    ['personnel', 'personnel.dashboard'],
]);

test('an unremembered account cannot sign in from the account icon', function () {
    $user = User::factory()->administrator()->create();

    $this->post(route('login.quick'), ['email' => $user->email])
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
