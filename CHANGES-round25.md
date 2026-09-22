RANIAG changelog — round 25 (OTP/trust rework, reporting-flow fixes, QR
poster rework)

Date: 2026-09-22

Eight field-reported items from the live system: a barangay field that
could be hand-edited after GPS/QR auto-fill (a regression), report
submissions getting stuck on the loading screen with no way out, a
plain Bootstrap warning modal that didn't fit the app's look, an OTP
flow with no resend option and no cap on wrong-code guesses, "trust this
device" still expiring after 30 days, a non-functional Remember-me
checkbox, no way to recognize a returning user on the login page, and a
QR poster page whose table and preview grid ran together with a
download that only exported the bare QR square.

Commit on `main` (this round):
  pending until push — see files list per section below.

HOW TO APPLY
Extract this zip over the existing project root (mirrors `raniag/`), then:
  php artisan migrate --force   (no new migrations this round — schema unchanged)
  php artisan view:clear && php artisan config:clear
Push `main` (GitHub Actions deploy). Hard-refresh /report, /login,
/admin/qr-posters so the updated JS/CSS isn't served from a stale cache.

=====================================================================
1. Barangay auto-fill regression — field is locked again
=====================================================================
Root cause: the `barangay` input on the public report form had no
`readonly` attribute, unlike the `location_address`/`latitude`/`longitude`
fields right next to it — so a barangay detected from GPS or a QR-code
prefill could be hand-edited afterward, unlike before.

Fix: `readonly aria-readonly="true"` added to match the other auto-filled
fields. The JS that sets the detected value still works — readonly only
blocks manual typing, not `.value` assignment from script.

Files:
  resources/views/public/report/create.blade.php

=====================================================================
2. Report submission — no longer gets stuck on the loading screen
=====================================================================
Root cause, two bugs stacked:
- The loading overlay is shown by the first submit listener registered
  on the form (in `public-report.js`). When offline, a second listener
  (`report-outbox.js`) intercepts the submit and cancels the real network
  request to queue it locally instead — but never hid the overlay it was
  shown by the first listener, so the reporter was left staring at
  "Processing, please wait…" forever with no request ever going out.
- That same offline handler's cleanup code looked for a button with id
  `submit-report`, which doesn't exist (the real id is `wizard-submit`),
  so even its own "re-enable the button" step silently failed.
- Separately, a page restored from the browser's back/forward cache could
  keep the overlay from the page it navigated away from.

Fix:
- `report-outbox.js` now calls `hideLoadingOverlay()` and targets the
  correct `#wizard-submit` button in its finally block.
- `public-report.js` calls `hideLoadingOverlay()` defensively on script
  boot (covers a fresh load and a validation-error redirect).
- `layouts/public.blade.php` hides the overlay on `pageshow` when the page
  was restored from bfcache, and exposes `showLoadingOverlay`/
  `hideLoadingOverlay` on `window` explicitly for other scripts to call.

Files:
  public/js/report-outbox.js
  public/js/public-report.js
  resources/views/layouts/public.blade.php

=====================================================================
3. Evidence-gate popup — restyled, backdrop dim fixed
=====================================================================
Root cause: the "No evidence attached" popup used stock Bootstrap modal
chrome instead of the app's alert language, and the page's scroll-progress
bar (z-index 2100) sat above Bootstrap's default modal/backdrop z-index
(1055/1050), so the dim layer didn't fully read as being behind the popup.

Fix: modal content now uses the same border-left accent + `--rg-radius`
treatment as the rest of the app's warning alerts (copy unchanged). Both
the backdrop and the modal itself were raised above the progress bar so
the full-page dim always renders correctly behind the popup.

Files:
  resources/views/public/report/create.blade.php

=====================================================================
4. QR posters — separated preview from the table, real full-poster download
=====================================================================
Root cause: the "Saved posters" table (Actions column on the right — kept,
that part was fine) sat directly above the poster-preview grid with no
visual separation, reading as one cluttered block. "Download PNG" only
exported the bare QR `<canvas>`, not the branded poster, and the poster
itself printed a raw report-URL text row that added visual noise for
someone scanning it.

Fix:
- Poster previews now live in their own titled card ("Poster previews"),
  matching the "Saved posters" card above it, with normal spacing between.
- `.raniag-poster-url` removed entirely — on-screen and print.
- `qr-poster.js`'s download rewritten to composite the *entire* poster
  (header bar with both seals, barangay label, QR with the MDRRMO seal,
  "scan to report" text, notes) onto one offscreen canvas at print
  resolution and export that — done with plain Canvas 2D
  (`drawImage`/`fillText`), no new dependency.

Files:
  resources/views/admin/qr_posters/index.blade.php
  public/js/qr-poster.js

=====================================================================
5. Trusted device — 30-day expiry replaced with permanent trust
=====================================================================
Root cause: `trusted_device_days` defaulted to 30, so a device that had
already passed OTP would still be asked for a fresh code a month later —
inconsistent with what "trust this device" implies.

Fix: `RANIAG_TWO_FACTOR_TRUSTED_DAYS` now defaults to `-1`, meaning trust
never expires until it's explicitly revoked — signing out does not clear
it, by design, since that would defeat the point of "trust this device".
`0` still means "force OTP every login" for kiosk/shared devices, and any
positive N keeps the original bounded-days behavior for ops who want a
finite override instead of permanent trust. The challenge page's copy
adjusts to match whichever mode is configured.

Files:
  app/Services/TwoFactorService.php
  config/raniag.php
  .env.example
  resources/views/auth/two-factor-challenge.blade.php

=====================================================================
6. Remember me — removed
=====================================================================
Root cause: the checkbox didn't do anything meaningful on top of the
trusted-device cookie and normal session lifetime, and wasn't working as
expected.

Fix: checkbox and all `remember`/`pending_2fa_remember` plumbing removed
from the login form, `LoginRequest`, `AuthenticatedSessionController`,
and `TwoFactorChallengeController`. Session behavior is unchanged
(`SESSION_LIFETIME`), and returning to a trusted device still skips OTP
regardless.

Files:
  resources/views/auth/login.blade.php
  app/Http/Requests/Auth/LoginRequest.php
  app/Http/Controllers/Auth/AuthenticatedSessionController.php
  app/Http/Controllers/Auth/TwoFactorChallengeController.php

=====================================================================
7. OTP reliability — resend + attempt cap
=====================================================================
Root cause: a stale/expired code was a dead end (no resend, had to go all
the way back and re-enter the password), and there was no limit on how
many codes could be guessed against the 10-minute TTL.

Fix:
- New "Resend" action on the challenge page, throttled to once every 30
  seconds with a live countdown, reusing the existing `LoginOtpMail`.
- Wrong codes are now capped at 5 attempts per pending login; hitting the
  cap kills the pending session and sends the user back to `/login`
  instead of allowing further guesses.

Files:
  app/Services/TwoFactorService.php
  app/Http/Controllers/Auth/TwoFactorChallengeController.php
  resources/views/auth/two-factor-challenge.blade.php
  routes/auth.php

=====================================================================
8. Quick login — recognizes a returning user on /login
=====================================================================
Root cause: every visit to `/login` showed a blank form even for someone
who had already signed in on that exact device/browser before.

Fix: a lightweight, non-sensitive cookie (name + email only, separate from
the security-bearing trusted-device cookie) is set on every successful
login. The bare `/login` page then shows a "Welcome back" card with the
recognized name/email, pre-fills and locks the email field, and autofocus
moves straight to password. A "Not you?" link clears the cookie and shows
the normal blank form. This cookie is untouched by logout — recognition
is meant to survive normal sign-out/sign-in cycles, only the "Not you?"
action clears it.

Files:
  app/Services/TwoFactorService.php
  app/Http/Controllers/Auth/AuthenticatedSessionController.php
  app/Http/Controllers/Auth/TwoFactorChallengeController.php
  resources/views/auth/login.blade.php
  public/css/auth.css
  routes/auth.php

=====================================================================
Verify after deploy
=====================================================================
1. /report — Location step: barangay field is greyed/readonly after GPS
   detection or a QR-prefilled visit, same as address/lat/lng
2. /report — submit while offline → overlay clears and shows the offline
   alert instead of hanging; submit while online with a validation error
   → returns to the erroring step with the overlay hidden, not stuck
3. /report — leave Evidence with nothing attached → popup uses the app's
   alert styling with a full dim behind it
4. /admin/qr-posters — "Saved posters" table and "Poster previews" are
   two distinct cards; Download PNG exports the whole branded poster
   (header, seals, barangay, QR, scan text) with no URL text on it
5. Login as admin/agency → OTP once → logout → log back in on the same
   browser → still no OTP prompt (was previously time-limited to 30 days)
6. Two-factor challenge page → Resend is disabled for 30s after sending,
   then enabled; 5 wrong codes in a row bounces back to /login
7. Login page has no Remember-me checkbox
8. Log in once, then visit /login again on the same browser → "Welcome
   back" card shows name/email; "Not you?" clears it back to a blank form
