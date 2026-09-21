<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class TwoFactorService
{
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

    private function cacheKey(int $userId): string
    {
        return "raniag.login_otp.{$userId}";
    }
}
