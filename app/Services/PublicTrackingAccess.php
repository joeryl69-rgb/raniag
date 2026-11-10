<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class PublicTrackingAccess
{
    public function isVerified(Request $request, Incident $incident): bool
    {
        if ($request->session()->get('track_verified.'.$incident->id) === true) {
            return true;
        }

        $cookie = $request->cookie($this->cookieName($incident));
        if (! is_string($cookie)) {
            return false;
        }

        [$expiresAt, $signature] = array_pad(explode('.', $cookie, 2), 2, '');
        if (! ctype_digit($expiresAt) || (int) $expiresAt <= now()->timestamp) {
            return false;
        }

        return hash_equals(
            $this->signature($incident, (int) $expiresAt),
            $signature,
        );
    }

    public function cookie(Incident $incident, Request $request): Cookie
    {
        $lifetime = max(1, (int) config('session.lifetime', 120));
        $expiresAt = now()->addMinutes($lifetime)->timestamp;
        $secure = config('session.secure');

        return cookie(
            $this->cookieName($incident),
            $expiresAt.'.'.$this->signature($incident, $expiresAt),
            $lifetime,
            '/track/'.$incident->tracking_number,
            config('session.domain'),
            $secure ?? $request->isSecure(),
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }

    public function shouldRefreshCookie(Request $request, Incident $incident): bool
    {
        $cookie = $request->cookie($this->cookieName($incident));
        if (! is_string($cookie)) {
            return true;
        }

        [$expiresAt, $signature] = array_pad(explode('.', $cookie, 2), 2, '');
        if (
            ! ctype_digit($expiresAt)
            || (int) $expiresAt <= now()->timestamp
            || ! hash_equals($this->signature($incident, (int) $expiresAt), $signature)
        ) {
            return true;
        }

        $refreshWindow = max(60, (int) config('session.lifetime', 120) * 30);

        return (int) $expiresAt - now()->timestamp <= $refreshWindow;
    }

    private function cookieName(Incident $incident): string
    {
        return 'raniag_track_'.$incident->id;
    }

    private function signature(Incident $incident, int $expiresAt): string
    {
        return hash_hmac(
            'sha256',
            'public-track|'.$incident->id.'|'.$expiresAt,
            (string) config('app.key'),
        );
    }
}
