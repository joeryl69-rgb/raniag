# RANIAG — Round 4: new icon, remove per-page loader, mobile login fix, announcement fix

## 1. New logo (shield removed)
- `public/images/icons/raniag.svg` — completely new mark: rounded-square
  badge (not a circle/shield outline), a map-pin (ties to *incident
  location* reporting) with an **"R" monogram** inside it, plus a small
  broadcast/signal arc accent — distinct from the old shield+starburst.
- All 8 PNG sizes regenerated from it (`icon-72` through `icon-512`).
- Every place that used a generic Bootstrap shield **glyph** as a stand-in
  for the brand mark now uses this real logo image instead, for
  consistency:
  - Public site navbar brand + footer brand (`layouts/public.blade.php`)
  - Staff portal sidebar brand (`layouts/app.blade.php`)
  - Login/forgot/reset panel mark — automatic, since it already pointed at
    `icon-192x192.png`.
- Left alone (out of scope, not the logo): the small `bi-shield-check` /
  `bi-shield-lock` icons used elsewhere as ordinary "secure/confidential"
  bullet icons — those are unrelated UI icons, not the brand mark.

## 2. Loading screen — removed from every navigation
- `<x-app-loader />` was sitting in the shared layout `<body>`, so it fired
  on **every page load**, not just when the installed app is opened — that
  was the performance hit you noticed.
- Removed it from both `layouts/public.blade.php` and `layouts/app.blade.php`,
  and deleted `components/app-loader.blade.php` entirely (no longer
  referenced anywhere).
- A one-time splash when the app icon is tapped on a phone's home screen is
  handled natively by the browser/OS from `manifest.json`'s icon +
  `background_color` — no custom HTML/JS needed for that, and it will
  never re-appear on in-app navigation.

## 3. Login page mobile responsiveness
- Root cause: on a phone running the installed PWA (standalone mode, no
  browser address bar), the brand panel had no reserved space for the
  status bar, so the mark rendered partly behind/above it.
- Added `viewport-fit=cover` to the auth pages' viewport meta, and
  `padding-top: max(Npx, env(safe-area-inset-top))` on `.auth-panel` at
  both the default and stacked-mobile breakpoints, plus a `100dvh` fallback
  for `.auth-shell` (mobile browser chrome resizing the viewport).
- Tagline **"Your safety is our priority"** was italic at 82% opacity —
  easy to miss. It's now bold, larger, gold (`#ffe8a3`), with a left accent
  bar, so it's the second thing you notice after the RANIAG title.
- Small polish pass so the card doesn't read as a generic auth template: a
  two-tone accent underline beneath the form title.

## 4. Announcements not appearing — hardened the create form
- The "New Announcement" modal's `<form>` had **no `action` attribute in
  the raw HTML** — it relied entirely on JavaScript
  (`openCreateModal()`) to set it at click-time. If anything on the page
  interfered with that script running, the form would silently submit
  nowhere useful instead of to the announcements endpoint.
- Added a static `action="{{ route('admin.announcements.store') }}"`
  directly on the form tag, so creating an announcement no longer depends
  on JS executing correctly. The edit flow (which does need JS, to swap in
  each announcement's own update URL) is unchanged.
- Everything else in the announcements pipeline — model, migration,
  controller, landing-page query — was already correct; if you'd created
  one before and still didn't see it, this was almost certainly why.

---

## What to do next
1. Extract the zip into your project root (7 files; overwrite).
2. **Delete manually:** `resources/views/components/app-loader.blade.php`
   (see `DELETE-THIS-FILE.txt` in the zip — this repo-patch format can only
   add/replace files, not delete, so this one needs a manual delete).
3. `php artisan route:clear && php artisan view:clear && php artisan config:clear`
4. Reload the app on your phone (uninstall/reinstall the PWA once, or clear
   site data, so the new icon + manifest are picked up — icons are cached
   more aggressively by the OS than regular pages).
5. Try **Admin → Updates & Announcements → New** again — it should now
   appear on the landing page immediately after saving.
6. Check the login page in portrait and landscape on your phone — the mark
   and tagline should sit fully on-screen with no clipping.
