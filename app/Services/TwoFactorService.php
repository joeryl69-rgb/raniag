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

    // Purely cosmetic — name/email only, never used for authorization.
    // Lets the login page greet a returning user by name and pre-fill
    // their email ("quick login"), independent of and unaffected by the
    // security-bearing trusted-device cookie above.
    public const RECOGNIZED_COOKIE = 'raniag_recognized_user';

    private const RECOGNIZED_COOKIE_DAYS = 180;

    public function requiredFor(User $user): bool
    {
        if (! config('raniag.two_factor.enabled', true)) {
            return false;
        }

        return $user->isAdministrator() || $user->isAgency();
    }

    public const MAX_VERIFY_ATTEMPTS = 5;

    private const RESEND_COOLDOWN_SECONDS = 30;

    public function sendLoginOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put($this->cacheKey($user->id), Hash::make($code), now()->addMinutes(
            (int) config('raniag.two_factor.otp_ttl_minutes', 10)
        ));
        Cache::forget($this->attemptsCacheKey($user->id));
        Cache::put(
            $this->resendCooldownKey($user->id),
            now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->getTimestamp(),
            now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)
        );

        Mail::to($user->email)->send(new LoginOtpMail($code, $user->name));
    }

    /**
     * How many seconds until the user can request another code (0 = can
     * resend now). Prevents mail-bombing an inbox by mashing "resend".
     */
    public function resendAvailableInSeconds(User $user): int
    {
        $availableAt = Cache::get($this->resendCooldownKey($user->id));
        if (! $availableAt) {
            return 0;
        }

        return max(0, $availableAt - now()->getTimestamp());
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
        Cache::forget($this->attemptsCacheKey($user->id));

        return true;
    }

    /**
     * Record a failed verification attempt and report whether the pending
     * login should now be killed. Without this, the 6-digit code (only
     * 1,000,000 combinations) could be brute-forced within the OTP's TTL
     * with no limit on guesses.
     */
    public function recordFailedAttempt(User $user): bool
    {
        $key = $this->attemptsCacheKey($user->id);
        $attempts = (int) Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, now()->addMinutes(
            (int) config('raniag.two_factor.otp_ttl_minutes', 10)
        ));

        return $attempts >= self::MAX_VERIFY_ATTEMPTS;
    }

    public function clearAttempts(User $user): void
    {
        Cache::forget($this->attemptsCacheKey($user->id));
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

    // Sentinel: trusted_device_days = -1 means trust never expires (until
    // the user signs out or an admin revokes it). 0 keeps its original
    // meaning of "force OTP every login" for kiosk/shared devices. Any
    // positive N keeps the original bounded-days behavior for ops who want
    // a finite override instead of permanent trust.
    private const PERMANENT_DAYS = -1;

    // ~20 years — effectively permanent without needing "no expiry" special
    // cases in Cache::put()/Cookie::make(), which both expect a duration.
    private const PERMANENT_TTL_DAYS = 20 * 365;

    /**
     * Whether trusted-device cookies should be issued at all. Ops can force
     * OTP on every login (e.g. shared/kiosk devices) by setting the trusted
     * days config to 0.
     */
    public function trustedDeviceEnabled(): bool
    {
        return (int) config('raniag.two_factor.trusted_device_days', self::PERMANENT_DAYS) !== 0;
    }

    /**
     * Whether trust, once granted, never expires (the default — this is
     * what makes "trust this device" actually stick instead of quietly
     * expiring after 30 days and forcing OTP again).
     */
    public function isPermanentTrust(): bool
    {
        return (int) config('raniag.two_factor.trusted_device_days', self::PERMANENT_DAYS) < 0;
    }

    public function issueTrustedDeviceCookie(User $user): SymfonyCookie
    {
        $configuredDays = (int) config('raniag.two_factor.trusted_device_days', self::PERMANENT_DAYS);
        $days = $configuredDays < 0 ? self::PERMANENT_TTL_DAYS : max(1, $configuredDays);
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

    /**
     * "Quick login" recognition — greets a returning user by name on the
     * bare /login page and pre-fills their email, the way most consumer
     * sites do for a device that has signed in before. Set on every
     * successful login regardless of role/2FA, and only ever cleared by
     * the explicit "Not you?" action (never by logging out — that would
     * defeat the point).
     */
    public function issueRecognizedUserCookie(User $user): SymfonyCookie
    {
        $value = base64_encode(json_encode([
            'name' => $user->name,
            'email' => $user->email,
        ]));

        return Cookie::make(
            self::RECOGNIZED_COOKIE,
            $value,
            self::RECOGNIZED_COOKIE_DAYS * 24 * 60,
            '/',
            null,
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    public function forgetRecognizedUserCookie(): SymfonyCookie
    {
        return Cookie::forget(self::RECOGNIZED_COOKIE);
    }

    /**
     * @return array{name: string, email: string}|null
     */
    public function recognizedUser(Request $request): ?array
    {
        $raw = $request->cookie(self::RECOGNIZED_COOKIE);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode((string) base64_decode($raw, true), true);
        if (! is_array($decoded) || empty($decoded['email']) || empty($decoded['name'])) {
            return null;
        }

        return ['name' => (string) $decoded['name'], 'email' => (string) $decoded['email']];
    }

    private function cacheKey(int $userId): string
    {
        return "raniag.login_otp.{$userId}";
    }

    private function attemptsCacheKey(int $userId): string
    {
        return "raniag.login_otp_attempts.{$userId}";
    }

    private function resendCooldownKey(int $userId): string
    {
        return "raniag.login_otp_resend.{$userId}";
    }

    private function trustedCacheKey(int $userId, string $token): string
    {
        return 'raniag.trusted_device.'.$userId.'.'.hash('sha256', $token);
    }
}
