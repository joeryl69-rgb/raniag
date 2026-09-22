<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request, TwoFactorService $twoFactor): View
    {
        return view('auth.login', [
            'recognizedUsers' => $twoFactor->recognizedUsers($request),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if ($user && $twoFactor->requiredFor($user)) {
            if ($twoFactor->hasTrustedDevice($request, $user)) {
                $request->session()->regenerate();

                return redirect()->intended(route($user->homeRoute(), absolute: false))
                    ->withCookie($twoFactor->issueRecognizedUserCookie($request, $user));
            }

            $request->session()->put('pending_2fa_id', $user->id);

            Auth::guard('web')->logout();
            $request->session()->regenerate();

            $twoFactor->sendLoginOtp($user->fresh());

            return redirect()->route('two-factor.challenge')
                ->with('status', 'We sent a verification code to your email.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute(), absolute: false))
            ->withCookie($twoFactor->issueRecognizedUserCookie($request, $user));
    }

    /**
     * Remove one remembered account ("x" on its avatar) or the whole list
     * ("Add another account" / no email given) from the login page's
     * account switcher. Deliberately separate from logout, which never
     * touches this — recognition is meant to survive normal sign-out/
     * sign-in cycles.
     */
    public function forgetDevice(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        return redirect()->route('login')
            ->withCookie($twoFactor->forgetRecognizedUser($request, $request->string('email')->toString() ?: null));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
