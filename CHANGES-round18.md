RANIAG changelog — round 18 (Security response headers: CSP, X-Frame-Options,
X-Content-Type-Options, Referrer-Policy, Cross-Origin-Opener-Policy, HSTS)

HOW TO APPLY
Extract this zip's `app/` and `bootstrap/` folders directly on top of your
existing project folder — 2 files, same paths, nothing else touched, no DB
impact.
Then: php artisan config:clear && php artisan cache:clear

=====================================================================
1) New file: app/Http/Middleware/SecurityHeaders.php
=====================================================================
Adds a middleware that appends security-relevant response headers to every
request. Follows the same shape as the existing EnsureUserIsActive /
PreventBackHistoryCache middleware (plain handle(), no config changes
required elsewhere).

Headers added:
- Content-Security-Policy — restricts script/style/font/img sources to
  'self' plus the CDN hosts the app already uses (cdn.jsdelivr.net,
  cdnjs.cloudflare.com, unpkg.com, fonts.bunny.net). object-src is fully
  blocked, base-uri and form-action are locked to 'self', frame-ancestors
  is locked to 'self'. Includes 'unsafe-inline' and 'unsafe-eval' because
  current views use inline <script>, inline onclick= handlers, and
  CDN-loaded Alpine.js — removing those two directives will break the UI
  until that inline code is migrated to external files or nonces.
- X-Frame-Options: SAMEORIGIN — blocks the site from being embedded in a
  frame on another origin (clickjacking).
- X-Content-Type-Options: nosniff — stops the browser from guessing a
  response's MIME type.
- Referrer-Policy: strict-origin-when-cross-origin — stops full internal
  URLs leaking to third-party sites via outbound links.
- Cross-Origin-Opener-Policy: same-origin.
- Strict-Transport-Security — sent only when the request is HTTPS AND
  app()->environment('production') is true, to avoid ever sending it from
  local/dev.

=====================================================================
2) Changed file: bootstrap/app.php
=====================================================================
One line added inside the existing withMiddleware() closure:
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
Nothing else in this file was touched — proxy trust config and cookie
encryption exceptions are unchanged.

=====================================================================
3) What this does NOT fix
=====================================================================
- Subresource Integrity (SRI) on the CDN <script>/<link> tags — still
  needs integrity= hashes added per tag, not covered by this round.
- APP_ENV / APP_DEBUG on the live server — verify directly on Hostinger
  that APP_ENV=production and APP_DEBUG=false. Not something a middleware
  can fix; it's a server-side .env value. If it's still "local" in
  production, the debug-session route in routes/web.php is publicly
  reachable and leaks live session IDs unauthenticated.

=====================================================================
4) Verify after deploy
=====================================================================
curl -I https://mediumorchid-weasel-407759.hostingersite.com
— confirm x-frame-options, x-content-type-options, referrer-policy,
content-security-policy, and (over HTTPS, in production)
strict-transport-security are present in the response headers. Then
re-run the Mozilla HTTP Observatory scan.
