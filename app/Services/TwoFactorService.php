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

    /**
     * Drop any in-flight OTP / attempt / resend state for a user (e.g. when
     * they remove the account from quick login or abandon the challenge).
     */
    public function clearLoginChallenge(User $user): void
    {
        Cache::forget($this->cacheKey($user->id));
        Cache::forget($this->attemptsCacheKey($user->id));
        Cache::forget($this->resendCooldownKey($user->id));
    }

    public function hasTrustedDevice(Request $request, User $user): bool
    {
        $raw = $request->cookie(self::TRUSTED_COOKIE);
        if (! is_string($raw) || $raw === '') {
            return false;
        }

        $entries = json_decode($raw, true);
        if (! is_array($entries)) {
            // Back-compat with the original single-user cookie format: user_id|token.
            $parts = explode('|', $raw, 2);
            if (count($parts) !== 2) {
                return false;
            }

            [$userId, $token] = $parts;
            if ((int) $userId !== (int) $user->id || $token === '') {
                return false;
            }

            $cacheKey = $this->trustedCacheKey((int) $user->id, $token);
            $stored = Cache::get($cacheKey);
            if ($stored === true || $stored === 1 || $stored === '1') {
                return true;
            }

            if ($stored === null) {
                $days = $this->isPermanentTrust()
                    ? self::PERMANENT_TTL_DAYS
                    : max(1, (int) config('raniag.two_factor.trusted_device_days', self::PERMANENT_DAYS));
                Cache::put($cacheKey, true, now()->addDays($days));

                return true;
            }

            return false;
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $entryUserId = (int) ($entry['user_id'] ?? 0);
            $token = (string) ($entry['token'] ?? '');
            if ($entryUserId !== (int) $user->id || $token === '') {
                continue;
            }

            $cacheKey = $this->trustedCacheKey($entryUserId, $token);
            $stored = Cache::get($cacheKey);
            if ($stored === true || $stored === 1 || $stored === '1') {
                return true;
            }

            // Deploy runs `optimize:clear`, which wipes the cache store. The
            // httpOnly encrypted cookie is still the source of truth for
            // "this browser was trusted" — re-hydrate the cache entry so
            // the next request stays fast without forcing another OTP.
            if ($stored === null && $token !== '') {
                $days = $this->isPermanentTrust()
                    ? self::PERMANENT_TTL_DAYS
                    : max(1, (int) config('raniag.two_factor.trusted_device_days', self::PERMANENT_DAYS));
                Cache::put($cacheKey, true, now()->addDays($days));

                return true;
            }
        }

        return false;
    }

    // Sentinel: trusted_device_days = -1 means trust never expires (until
    // explicitly revoked). Signing out does not clear it. 0 keeps its
    // original meaning of "force OTP every login" for kiosk/shared devices.
    // Any positive N keeps the original bounded-days behavior for ops who
    // want a finite override instead of permanent trust.
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

        $entries = [];
        $cookieValue = Cookie::get(self::TRUSTED_COOKIE);
        if (is_string($cookieValue) && $cookieValue !== '') {
            $decoded = json_decode($cookieValue, true);
            if (is_array($decoded)) {
                $entries = $decoded;
            } else {
                $legacy = explode('|', $cookieValue, 2);
                if (count($legacy) === 2) {
                    $entries = [[ 'user_id' => (int) $legacy[0], 'token' => (string) $legacy[1] ]];
                }
            }
        }

        $entries = array_values(array_filter(
            $entries,
            fn (array $entry) => (int) ($entry['user_id'] ?? 0) !== (int) $user->id
        ));

        $entries[] = ['user_id' => (int) $user->id, 'token' => $token];

        return Cookie::make(
            self::TRUSTED_COOKIE,
            json_encode($entries, JSON_THROW_ON_ERROR),
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
     * "Quick login" recognition — a Facebook/Google-style account switcher
     * on the bare /login page. Every account that has ever signed in
     * successfully on this browser is listed (most-recent-first, no cap),
     * so returning staff can tap their avatar instead of retyping an
     * email. Purely cosmetic — name/email only, never used for
     * authorization — and independent of the security-bearing
     * trusted-device cookie above. Set on every successful login
     * regardless of role/2FA, and only ever removed by the explicit
     * per-account "remove" action (never by logging out — that would
     * defeat the point of remembering the device).
     *
     * @return list<array{name: string, email: string}>
     */
    public function recognizedUsers(Request $request): array
    {
        $raw = $request->cookie(self::RECOGNIZED_COOKIE);
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode((string) base64_decode($raw, true), true);
        if (! is_array($decoded)) {
            return [];
        }

        $accounts = [];
        foreach ($decoded as $entry) {
            if (is_array($entry) && ! empty($entry['email']) && ! empty($entry['name'])) {
                $accounts[] = ['name' => (string) $entry['name'], 'email' => (string) $entry['email']];
            }
        }

        return $accounts;
    }

    /**
     * Add/move a user to the front of the recognized-accounts list on this
     * browser, de-duplicated by email.
     */
    public function issueRecognizedUserCookie(Request $request, User $user): SymfonyCookie
    {
        $accounts = $this->recognizedUsers($request);

        $accounts = array_values(array_filter(
            $accounts,
            fn (array $account) => strcasecmp($account['email'], $user->email) !== 0
        ));

        array_unshift($accounts, ['name' => $user->name, 'email' => $user->email]);

        return $this->makeRecognizedCookie($accounts);
    }

    /**
     * Remove a single account from the recognized list (the "x" on its
     * avatar), or clear the whole list when no email is given.
     */
    public function forgetRecognizedUser(Request $request, ?string $email = null): SymfonyCookie
    {
        if ($email === null) {
            return Cookie::forget(self::RECOGNIZED_COOKIE);
        }

        $accounts = array_values(array_filter(
            $this->recognizedUsers($request),
            fn (array $account) => strcasecmp($account['email'], $email) !== 0
        ));

        if (empty($accounts)) {
            return Cookie::forget(self::RECOGNIZED_COOKIE);
        }

        return $this->makeRecognizedCookie($accounts);
    }

    public function forgetTrustedDevice(Request $request, User $user): SymfonyCookie
    {
        $raw = $request->cookie(self::TRUSTED_COOKIE);
        if (! is_string($raw) || $raw === '') {
            return Cookie::forget(self::TRUSTED_COOKIE);
        }

        $entries = json_decode($raw, true);
        if (! is_array($entries)) {
            // Legacy single-user format: drop the whole cookie if it matches.
            $parts = explode('|', $raw, 2);
            if (count($parts) === 2 && (int) $parts[0] === (int) $user->id) {
                Cache::forget($this->trustedCacheKey((int) $user->id, (string) $parts[1]));

                return Cookie::forget(self::TRUSTED_COOKIE);
            }

            return Cookie::forget(self::TRUSTED_COOKIE);
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            if ((int) ($entry['user_id'] ?? 0) !== (int) $user->id) {
                continue;
            }
            $token = (string) ($entry['token'] ?? '');
            if ($token !== '') {
                Cache::forget($this->trustedCacheKey((int) $user->id, $token));
            }
        }

        $filtered = array_values(array_filter(
            $entries,
            fn (array $entry) => (int) ($entry['user_id'] ?? 0) !== (int) $user->id
        ));

        if (empty($filtered)) {
            return Cookie::forget(self::TRUSTED_COOKIE);
        }

        return Cookie::make(
            self::TRUSTED_COOKIE,
            json_encode($filtered, JSON_THROW_ON_ERROR),
            self::PERMANENT_TTL_DAYS * 24 * 60,
            '/',
            null,
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    /**
     * Wipe every trusted-device entry on this browser (used when clearing
     * the whole quick-login account list).
     */
    public function forgetAllTrustedDevices(Request $request): SymfonyCookie
    {
        $raw = $request->cookie(self::TRUSTED_COOKIE);
        if (is_string($raw) && $raw !== '') {
            $entries = json_decode($raw, true);
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $userId = (int) ($entry['user_id'] ?? 0);
                    $token = (string) ($entry['token'] ?? '');
                    if ($userId > 0 && $token !== '') {
                        Cache::forget($this->trustedCacheKey($userId, $token));
                    }
                }
            } else {
                $parts = explode('|', $raw, 2);
                if (count($parts) === 2) {
                    Cache::forget($this->trustedCacheKey((int) $parts[0], (string) $parts[1]));
                }
            }
        }

        return Cookie::forget(self::TRUSTED_COOKIE);
    }

    /**
     * @param  list<array{name: string, email: string}>  $accounts
     */
    private function makeRecognizedCookie(array $accounts): SymfonyCookie
    {
        return Cookie::make(
            self::RECOGNIZED_COOKIE,
            base64_encode(json_encode($accounts)),
            self::RECOGNIZED_COOKIE_DAYS * 24 * 60,
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
