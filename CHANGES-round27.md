RANIAG changelog — round 27 (public UX polish Phase 1: JO guide,
live hazard map, 2FA quick-login fix)

Date: 2026-09-23

Public outer-page pass: first-time guide character **JO** (MDRRMO field
responder) across landing nav + report wizard, a live/dynamic hazard map,
desktop scroll/map jank fix with Lenis, and a 2FA bug when removing an
account from quick login then signing in again. Staff interiors unchanged.

Commit on `main` (this round):
  see git log after push — Round 27 public UX polish.

HOW TO APPLY
Push `main` (GitHub Actions deploy already runs composer/npm/migrate/
optimize). No new migrations this round — schema unchanged.
After deploy:
  php artisan optimize:clear   (GHA already does this)
  Hard-refresh /, /report, /hazard-map, /login so JS/CSS/SVG are not
  served from a stale browser or CDN cache.
  Optional: clear Hostinger website cache if the live site still looks old.

Live site:
  https://mediumorchid-weasel-407759.hostingersite.com

=====================================================================
1. 2FA / quick-login — remove account now revokes trusted device
=====================================================================
Bug: clicking × on a quick-login account only cleared the cosmetic
`raniag_recognized_user` cookie. The security `raniag_trusted_device`
cookie and pending OTP session stayed, so re-entering credentials could
skip 2FA.

Fix:
- `forgetDevice` resolves the user by email, calls `forgetTrustedDevice`,
  clears OTP/attempt cache via `clearLoginChallenge`, and clears
  matching `pending_2fa_id`.
- Clearing the whole switcher list also forgets all trusted devices.
- Trusted-device login success clears stale `pending_2fa_id`.
- “Back to sign in” on the challenge page is POST `two-factor.cancel`
  and clears `pending_2fa_id` (no longer a bare link).

Files:
  app/Services/TwoFactorService.php
  app/Http/Controllers/Auth/AuthenticatedSessionController.php
  app/Http/Controllers/Auth/TwoFactorChallengeController.php
  resources/views/auth/two-factor-challenge.blade.php
  routes/auth.php
  tests/Feature/Auth/TwoFactorQuickLoginTest.php

=====================================================================
2. Desktop scroll / map jank (Lenis vs Leaflet)
=====================================================================
Wheel events over maps were fighting global Lenis smooth scroll.

Fix: `data-lenis-prevent` on `#hazard-map` and `#incident-map`. Hazard
map also calls `invalidateSize` on load/resize.

Files:
  resources/views/public/hazard/map.blade.php
  resources/views/public/report/create.blade.php

=====================================================================
3. Live hazard map redesign
=====================================================================
Replaced the plain Leaflet box with situational-awareness chrome:
- `rg-page-head` + LIVE pill + “Updated Ns ago”
- Map stage + side panel (zones, centers, nearest, legend, layer toggles)
- Custom evacuation markers with radar ping; pulsing hazard polygons
- Soft refresh via `GET /hazard-map/snapshot` every ~60s
- Respects `prefers-reduced-motion`

Files:
  resources/views/public/hazard/map.blade.php
  public/js/public-hazard-map.js
  app/Http/Controllers/Public/HazardMapController.php
  routes/public.php
  public/css/public.css
  tests/Feature/Public/HazardMapTest.php

=====================================================================
4. JO guide — first-time site tour + report coach
=====================================================================
Flat-vector MDRRMO responder **JO** (`public/images/guide/jo-*.svg`).

Prefs in `localStorage` key `raniag_guide`:
- `siteTourDone` — navbar purpose tour finished/skipped
- `reportCoachDismissed` — report coach hidden after dismiss/success
- `seenPages` — one-shot tips per page

Behavior:
- First visit: “Hi, I’m JO…” dock with Show me / Skip
- Tour spotlights Home, Track, Hazard Map, Community Dashboard,
  Support, Report an Incident
- Home “How it works” card adds Start guided tour
- Footer: Ask JO again
- Report wizard: desktop sticky coach + mobile bottom bar for
  first-timers; Need help? / Don’t show guide again for return visits
- Success page uses JO resolved pose

Files:
  public/js/public-guide.js
  public/images/guide/*.svg
  resources/views/layouts/public.blade.php
  resources/views/public/home.blade.php
  resources/views/public/report/create.blade.php
  resources/views/public/report/success.blade.php
  public/js/public-report.js
  public/css/public.css

=====================================================================
5. Light polish — other public pages
=====================================================================
Track copy mentions JO; community dashboard uses `rg-page-head`
alignment with the rest of the public shell.

Files:
  resources/views/public/track/index.blade.php
  resources/views/public/dashboard.blade.php

=====================================================================
Verify after deploy
=====================================================================
1. Fresh browser / private window on home → JO offer appears;
   Show me walks the navbar; Skip silences auto-tour.
2. /report → JO coach for first-timers; dismiss → Need help? works.
3. /hazard-map → LIVE badge, pulsing zones, panel toggles; scroll page
   vs zoom map without stickiness.
4. Staff login: trust a device → remove account via × → login again →
   OTP challenge required (not skip to dashboard).
5. Challenge “Back to sign in” clears pending challenge.
6. Footer Ask JO again replays the site tour.
