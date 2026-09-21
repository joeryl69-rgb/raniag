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
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('pending_2fa_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'trust_device' => ['sometimes', 'boolean'],
        ]);

        $userId = $request->session()->get('pending_2fa_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->is_active) {
            $request->session()->forget(['pending_2fa_id', 'pending_2fa_remember']);

            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        if (! $twoFactor->verify($user, $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $remember = (bool) $request->session()->pull('pending_2fa_remember', false);
        $request->session()->forget('pending_2fa_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $response = redirect()->intended(route($user->homeRoute(), absolute: false));

        if ($request->boolean('trust_device')) {
            $response->withCookie($twoFactor->issueTrustedDeviceCookie($user));
        }

        return $response;
    }
}
