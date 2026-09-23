<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request, TwoFactorService $twoFactor): View|RedirectResponse
    {
        $userId = $request->session()->get('pending_2fa_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::query()->find($userId);

        return view('auth.two-factor-challenge', [
            'resendAvailableInSeconds' => $user ? $twoFactor->resendAvailableInSeconds($user) : 0,
        ]);
    }

    /**
     * Abandon the pending OTP challenge and return to the password form.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('pending_2fa_id');

        return redirect()->route('login');
    }

    /**
     * Re-send a fresh OTP to the same pending login, throttled so mashing
     * the button can't be used to spam the reporter's inbox. Also gives a
     * genuine dead end (expired code, nothing arrived) a way out besides
     * going all the way back to re-enter the password.
     */
    public function resend(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get('pending_2fa_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->is_active) {
            $request->session()->forget('pending_2fa_id');

            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        $wait = $twoFactor->resendAvailableInSeconds($user);
        if ($wait > 0) {
            return back()->withErrors(['code' => "Please wait {$wait}s before requesting another code."]);
        }

        $twoFactor->sendLoginOtp($user);

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = $request->session()->get('pending_2fa_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->is_active) {
            $request->session()->forget('pending_2fa_id');

            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        if (! $twoFactor->verify($user, $request->string('code')->toString())) {
            // Cap the number of guesses against the 6-digit code (1M
            // combinations) instead of allowing unlimited attempts within
            // the OTP's TTL.
            if ($twoFactor->recordFailedAttempt($user)) {
                $request->session()->forget('pending_2fa_id');

                return redirect()->route('login')->withErrors([
                    'email' => 'Too many incorrect codes. Please sign in again.',
                ]);
            }

            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $twoFactor->clearAttempts($user);
        $request->session()->forget('pending_2fa_id');

        Auth::login($user);
        $request->session()->regenerate();

        $response = redirect()->intended(route($user->homeRoute(), absolute: false))
            ->withCookie($twoFactor->issueRecognizedUserCookie($request, $user));

        $rememberDevice = $request->boolean('remember_device', true);

        // Trust is explicit on the challenge screen, and it is stored per user so
        // the same browser can remember multiple accounts (admin + non-admin)
        // without clobbering the other one. This matches the Facebook/Google
        // account-switcher pattern, while still allowing kiosk/shared devices to
        // force OTP on every login with trusted_device_days = 0.
        if ($rememberDevice && $twoFactor->trustedDeviceEnabled()) {
            $response->withCookie($twoFactor->issueTrustedDeviceCookie($user));
        }

        return $response;
    }
}
