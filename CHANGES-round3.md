# RANIAG — Round 3 fix

## Bug found & fixed
- `public/manifest.json` had `"orientation": "portrait-primary"`, which
  **locks the installed PWA to portrait and blocks landscape** — the
  opposite of the landscape-accessibility support you asked for. Changed
  to `"orientation": "any"`. (The auth pages' own landscape/short-viewport
  CSS in `public/css/auth.css` was already correct — this manifest setting
  was the one thing overriding it at the OS/install level.)

## Audited, found correct (no changes needed)
- Login flow: `AuthenticatedSessionController`, `login.blade.php`,
  `User::homeRoute()`, `EnsureUserIsActive` middleware, proxy/HTTPS
  handling (`trustProxies(at: '*')` + `URL::forceScheme('https')`) — all
  check out in the code.
- Announcements: `HomeController` queries `Announcement::published()`,
  `public/home.blade.php` renders the section, admin CRUD exists and is
  linked in the sidebar. **This is working as built — it shows "No
  announcements yet" because none have been created in the database yet.**
  Go to **Admin → Updates & Announcements → New** and publish one; it will
  then appear on the landing page immediately.
- Help FAB + loading screen: confirmed wired into both
  `layouts/public.blade.php` and `layouts/app.blade.php` (staff portal).
- Incident Types "Reset to Default": confirmed using per-row
  `default_icon`/`default_color` for every type, not just the original 8.

## Still need from you, to diagnose "cannot login"
The login code itself has no defect I can find. This is almost always one
of these — please check and tell me which, or send the exact error/screenshot:
1. **Wrong or inactive account** — does the login page show a red error
   message at all, or does the page just do nothing?
2. **"Your account has been deactivated"** message — means `is_active` is
   0 for that user in the DB (check `users` table).
3. **419 Page Expired** — session/CSRF mismatch, common right after the
   ngrok URL changes; hard-refresh the login page first, then try again.
4. **A different 500 error page** — please paste it, same as the last two
   bugs; I fixed both from the exact trace you sent.
