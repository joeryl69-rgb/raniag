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
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();
        $remember = $request->boolean('remember');

        if ($user && $twoFactor->requiredFor($user)) {
            $request->session()->put('pending_2fa_id', $user->id);
            $request->session()->put('pending_2fa_remember', $remember);

            Auth::guard('web')->logout();
            $request->session()->regenerate();

            $twoFactor->sendLoginOtp($user->fresh());

            return redirect()->route('two-factor.challenge')
                ->with('status', 'We sent a verification code to your email.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute(), absolute: false));
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
