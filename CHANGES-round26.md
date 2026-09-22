RANIAG changelog — round 26 (modal freeze fix, QR poster rework,
multi-account quick login)

Date: 2026-09-22

Five field-reported items after round 25 went live: the report page's
GPS camera modal (and the evidence-gate warning behind it) freezing
the page so nothing is clickable, the mobile bottom dock printing
across every page of a QR poster printout, the QR poster dropdown
letting the same barangay be picked more than once, the QR Posters
admin page cramming a create form, a table, and a full preview grid
onto one screen, and the single-account "quick login" not matching the
Facebook-style multi-account switcher that was actually wanted.

Commit on `main` (this round):
  pending until push — see files list per section below.

HOW TO APPLY
Extract this zip over the existing project root (mirrors `raniag/`), then:
  php artisan migrate --force
  php artisan view:clear && php artisan config:clear
Push `main` (GitHub Actions deploy). Hard-refresh /report, /login,
/admin/qr-posters so the updated JS/CSS isn't served from a stale cache.

=====================================================================
1. Report page freeze — GPS camera modal and evidence-gate modal both
   unclickable
=====================================================================
Root cause: round 25 fixed the evidence-gate modal's dim by forcing
`.modal-backdrop.show { z-index: 2150 }` and `#evidence-gate-modal {
z-index: 2160 }` directly in create.blade.php. `.modal-backdrop.show`
is a global Bootstrap class — it also applies to the GPS camera
modal's backdrop, which was never given a matching bump. Its backdrop
(2150) then sat above the camera modal itself (default ~1055),
covering it completely — nothing in the camera view was clickable, and
the same leftover backdrop is what blocked the Submit button
afterward ("stuck at loading screen").

The actual source of the original dim problem was `#rg-progress` (the
scroll-progress bar) sitting at `z-index: 2100` in
layouts/public.blade.php, above Bootstrap's modal/backdrop stack
(1050/1055) — it was poking through every modal's dim layer, not just
the evidence-gate one.

Fix: removed the global `.modal-backdrop.show`/`#evidence-gate-modal`
z-index overrides. Lowered `#rg-progress` to `z-index: 1045` (still
above the navbar's 1040, but below Bootstrap's modal stack), which
fixes the dim-stacking issue for every modal on the page instead of
hacking one modal upward. The evidence-gate modal keeps its restyled
border/shadow — only the z-index hack was removed.

Files:
  resources/views/public/report/create.blade.php
  resources/views/layouts/public.blade.php

=====================================================================
2. Mobile bottom dock printing across every page
=====================================================================
Root cause: the mobile dock only hides itself via `@media
(min-width: 992px)`. Chrome's print layout emulates a narrow page
width, which satisfies that mobile-only rule and rendered the dock
across the bottom of every printed page (seen on printed QR posters,
but true for any admin page printed from a narrow viewport).

Fix: added `@media print { .mobile-dock { display: none !important; }
}` to the component's existing inline style block.

Files:
  resources/views/components/mobile-dock.blade.php

=====================================================================
3. One QR poster per barangay (strict)
=====================================================================
Root cause: `QrPosterController` had no uniqueness rule on `barangay`,
and the create/edit dropdown always listed all 18 barangays regardless
of existing posters — so the same barangay could be picked repeatedly.

Fix:
- New migration converts the plain index on `qr_posters.barangay` to a
  unique index.
- `store()`/`update()` now validate `barangay` with
  `Rule::unique('qr_posters', 'barangay')->ignore($qrPoster?->id)`, so
  duplicates are rejected server-side regardless of the UI.
- `index()` now passes only the barangays that don't already have a
  poster to the view (the poster currently being edited keeps its own
  barangay selectable).

Files:
  database/migrations/2026_09_22_100000_make_qr_posters_barangay_unique.php (new)
  app/Http/Controllers/Admin/QrPosterController.php
  resources/views/admin/qr_posters/index.blade.php

=====================================================================
4. QR Posters admin page — split into tabs
=====================================================================
Root cause: the create form, the saved-posters table, and the
full-size branded preview grid were all stacked on one page — the
same "everything at once" problem the Hazard page had before its
round-24 tab rework.

Fix: reorganized into two tabs using the same `nav-tabs`/`tab-pane`
pattern already established on the Hazard page:
- "Manage posters" — create/edit form + saved-posters table (same
  left/right layout as before, scoped to this tab).
- "Poster previews & print" — the branded poster grid and the "Print
  active posters" button, full width.
Printing forces the previews tab visible and the manage tab hidden via
`@media print`, regardless of which tab is active on screen, so
`window.print()` always prints the posters.

Files:
  resources/views/admin/qr_posters/index.blade.php

=====================================================================
5. Quick login — reworked into a Facebook-style multi-account switcher
=====================================================================
Root cause: round 25 shipped a single-account "welcome back" card
(one name/email in a cookie). The actual request was a multi-account
switcher like Facebook/Google use, with an icon per remembered
account and its own remove control — not one recognized user at a
time.

Fix:
- `TwoFactorService`'s recognized-user cookie now holds a JSON array of
  `{name, email}` (most-recent-first, de-duplicated by email, no cap),
  appended to on every successful login (password-only and
  post-OTP) via `issueRecognizedUserCookie(Request, User)`.
- `forgetRecognizedUser(Request, ?email)` removes a single account by
  email ("x" on its avatar) or clears the whole list when no email is
  given.
- Login page now renders every remembered account as a clickable
  avatar-initial + name/email row: one click pre-fills and locks the
  email field and focuses the password field — no separate "continue"
  button. Each row has its own small remove icon. An "Add another
  account" row reveals a blank, unlocked form. A "Choose a different
  account" link returns to the switcher from either state.
- Layout is a simple vertical list (avatar, name/email with ellipsis
  truncation, remove icon), so it looks and behaves the same on mobile
  as on desktop — no separate mobile variant needed.
- `AuthenticatedSessionController::forgetDevice()` now reads an
  optional `email` from the request to target a single account.

Files:
  app/Services/TwoFactorService.php
  app/Http/Controllers/Auth/AuthenticatedSessionController.php
  app/Http/Controllers/Auth/TwoFactorChallengeController.php
  resources/views/auth/login.blade.php
  public/css/auth.css

=====================================================================
VERIFICATION
=====================================================================
- php -l on every touched PHP file — no syntax errors.
- php artisan view:cache — every touched Blade view compiles.
- Report page: opened the evidence-gate warning and the GPS camera
  modal separately — both backdrops dim correctly and stay clickable;
  closing either leaves no lingering backdrop blocking Submit.
- QR Posters: create/edit form only lists barangays without a poster;
  attempting to force a duplicate via a direct POST is rejected by the
  unique validation rule; Manage/Previews tabs render independently
  and "Print active posters" still shows the branded grid regardless
  of which tab was open on screen.
- Mobile dock: print preview on an admin page no longer shows the
  bottom nav bar.
- Login: with two+ accounts recognized on a browser, the switcher
  lists both with independent remove icons; selecting one locks the
  email and focuses password; "Add another account" clears/unlocks
  the email field; "Choose a different account" returns to the list.
