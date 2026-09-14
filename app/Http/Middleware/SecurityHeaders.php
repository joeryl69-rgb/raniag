<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Adds the security headers flagged as missing by Mozilla HTTP Observatory.
     *
     * The CSP below is intentionally NOT strict (it allows 'unsafe-inline' and
     * 'unsafe-eval') because the current views rely on inline <script> blocks,
     * inline onclick= handlers, and Alpine.js via CDN. It still meaningfully
     * reduces risk by:
     *   - restricting which THIRD-PARTY hosts scripts/styles/images may load from
     *   - blocking <object>/<embed>/Flash-style content entirely
     *   - blocking the page from being framed by any other origin (clickjacking)
     *   - blocking base tag hijacking
     *
     * To get Observatory's CSP points too, inline scripts/styles need to move to
     * external files or a nonce (see TODO at the bottom of this file).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // Only send HSTS over an actual HTTPS request, and only in production.
        // Sending it in local/dev can lock your browser into HTTPS for that
        // hostname for months, which is painful to undo.
        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
