<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class TwoFactorService
{
    public const TRUSTED_COOKIE = 'raniag_trusted_device';

    public function requiredFor(User $user): bool
    {
        if (! config('raniag.two_factor.enabled', true)) {
            return false;
        }

        return $user->isAdministrator() || $user->isAgency();
    }

    public function sendLoginOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put($this->cacheKey($user->id), Hash::make($code), now()->addMinutes(
            (int) config('raniag.two_factor.otp_ttl_minutes', 10)
        ));

        Mail::to($user->email)->send(new LoginOtpMail($code, $user->name));
    }

    public function verify(User $user, string $code): bool
    {
        $hash = Cache::get($this->cacheKey($user->id));
        if (! is_string($hash) || $hash === '') {
            return false;
        }

        if (! Hash::check($code, $hash)) {
            return false;
        }

        Cache::forget($this->cacheKey($user->id));

        return true;
    }

    public function hasTrustedDevice(Request $request, User $user): bool
    {
        $raw = $request->cookie(self::TRUSTED_COOKIE);
        if (! is_string($raw) || $raw === '') {
            return false;
        }

        $parts = explode('|', $raw, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$userId, $token] = $parts;
        if ((int) $userId !== (int) $user->id || $token === '') {
            return false;
        }

        $stored = Cache::get($this->trustedCacheKey((int) $user->id, $token));

        return $stored === true || $stored === 1 || $stored === '1';
    }

    /**
     * Whether trusted-device cookies should be issued at all. Ops can force
     * OTP on every login (e.g. shared/kiosk devices) by setting the trusted
     * days config to 0.
     */
    public function trustedDeviceEnabled(): bool
    {
        return (int) config('raniag.two_factor.trusted_device_days', 30) > 0;
    }

    public function issueTrustedDeviceCookie(User $user): SymfonyCookie
    {
        $days = max(1, (int) config('raniag.two_factor.trusted_device_days', 30));
        $token = Str::random(64);

        Cache::put(
            $this->trustedCacheKey((int) $user->id, $token),
            true,
            now()->addDays($days)
        );

        $value = $user->id.'|'.$token;

        return Cookie::make(
            self::TRUSTED_COOKIE,
            $value,
            $days * 24 * 60,
            '/',
            null,
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    private function cacheKey(int $userId): string
    {
        return "raniag.login_otp.{$userId}";
    }

    private function trustedCacheKey(int $userId, string $token): string
    {
        return 'raniag.trusted_device.'.$userId.'.'.hash('sha256', $token);
    }
}
