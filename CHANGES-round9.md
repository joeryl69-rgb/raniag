# RANIAG update package — what's inside and how to apply it

This zip contains **only new/changed files**, in the same folder layout as your
project. Extract it directly into your project root and let it merge/overwrite
— it will not create a second `raniag/` folder.

## 1. Extract
Unzip so that `raniag/app/...`, `raniag/routes/...` etc. land on top of your
existing `raniag/` project folder (overwrite when prompted).

## 2. Install the new PHP dependency (Web Push)
```
composer require minishlink/web-push
```
(It's already added to `composer.json` in this package — this just downloads it.)

## 3. Run the new migrations
```
php artisan migrate
```
Adds: `personnel_roles`, `system_settings`, `push_subscriptions`.

## 4. Seed the existing personnel role titles into the new table
```
php artisan db:seed --class=Database\\Seeders\\PersonnelRoleSeeder
```
(Or just re-run `php artisan db:seed` — it's already wired into `DatabaseSeeder`
and uses `firstOrCreate`, so it's safe even on a database that already has data.)

## 5. Generate VAPID keys for push notifications
```
php artisan raniag:generate-vapid-keys
```
Copy the three printed lines into your `.env`:
```
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:your-admin-email@example.com
```
Then:
```
php artisan config:clear
```
Without these, the rest of the system works normally — push notifications
just silently no-op (logged, not fatal) until the keys are set.

## 6. Clear caches (recommended after any route/view change)
```
php artisan route:clear && php artisan view:clear && php artisan config:clear
```

---

## What changed, feature by feature

### A. Dynamic personnel roles (was a static array)
- New table `personnel_roles` + model + seeder (pre-filled with your 7 existing titles).
- New admin CRUD at **Sidebar → Coordination → Agencies & Personnel → Personnel Roles**
  (`/admin/personnel-roles`) — add, rename, deactivate, delete.
- `AgencyController` (create account) and `PersonnelController` (edit account) now pull
  the dropdown from this table instead of a hardcoded list.
- Renaming a role automatically updates existing personnel accounts that used the old
  title. Deleting is blocked while a role is still in use — deactivate instead.
- The sidebar's "Agencies & Personnel" item is now an expandable group with two links:
  **Accounts List** and **Personnel Roles**.

### B. Profile moved to the top navbar (Facebook-style)
- New `x-profile-menu` component: avatar button beside the notification bell, opens a
  dropdown with My Profile, a push-notification toggle, System Settings (admin), Support
  Center (agency/personnel), and Log Out.
- The old sidebar "profile widget" block (bottom of the sidebar) was removed.

### C. Floating bottom navbar (mobile only)
- New `x-mobile-dock` component: a floating pill dock fixed to the bottom of the screen
  on phones/tablets (hidden ≥992px width), with role-aware quick links (Dashboard,
  Incidents/Dispatches, Agencies, Support, Alerts, and "More" which opens the full
  sidebar). Respects the iOS/Android safe-area inset so it never overlaps the home
  indicator.
- Page content gets bottom padding on mobile so the dock never covers anything.

### D. System Settings — theme & dark mode
- New table `system_settings` (one row = current settings for the whole system).
- New page **Sidebar → Oversight → System Settings** (`/admin/settings`): 6 sample color
  themes (Ocean Blue [default], Emerald, Sunset, Crimson, Royal Purple, Slate) plus a
  Dark Mode switch, with live preview before saving, and a **Reset to Default** button.
- Every theme maps onto the `--raniag-*` CSS variables your whole UI already uses
  (109 usages across every layout — public, admin, agency, personnel), so switching
  themes recolors the entire interface, not just isolated bits. Dark mode adds a matching
  CSS block (`public/css/public.css`) covering cards, tables, forms, and dropdowns that
  aren't driven by those variables.
- Applied globally by injecting the current theme as inline CSS variables in
  `layouts/app.blade.php`'s `<head>` — no per-page opt-in needed.

### E. Push notifications (works even when the app/tab is closed)
- Standard **Web Push** (the same browser API Messenger/Gmail use) via VAPID + the
  `minishlink/web-push` package — not a custom/fragile implementation.
- New table `push_subscriptions` (one row per browser/device a user has enabled
  notifications on).
- `public/sw.js` now listens for `push` events (shows an OS notification with title/body/
  icon even if no RANIAG tab is open) and `notificationclick` (focuses an open tab or
  opens a new one to the relevant page).
- `NotificationService::notify()` — the same method that already creates every in-app
  bell notification — now also queues a push to the recipient's devices, so every
  existing notification type (incident assigned, status update, etc.) automatically
  gets push delivery with zero changes needed elsewhere.
- Users turn it on themselves via **profile menu → Enable Push Notifications** (browser
  permission prompt), matching how Messenger/Gmail opt-in works.

## Notes / things to double check on your end
- I could not run `composer install`, `php artisan migrate`, or a PHP linter in this
  sandbox (no PHP runtime or Packagist access here), so please run a normal
  `php artisan migrate` + smoke-test on a dev/staging copy before production.
- The dark-mode CSS pass covers the main shared surfaces (cards, tables, forms, modals,
  dropdowns). If you spot one specific page with a hardcoded white background instead of
  a Bootstrap/variable-driven one, send me that page and I'll patch it directly.
- Theming currently applies to the authenticated staff portal (`layouts/app.blade.php` —
  admin/agency/personnel). The public landing/tracking pages keep their current fixed
  brand colors on purpose; say the word if you want those themed too.
