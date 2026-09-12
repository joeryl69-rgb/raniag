wheres the changelog for round 11

RANIAG changelog — Round 11

Overview
This round replaces the global system-wide appearance settings with a real per-user model, upgrades the settings UI (slider, real toggle disabling, mobile fix), adds a desktop sidebar collapse, and tracks down the logout/permission bug to its actual root cause.

1) Independent per-user appearance settings (the big architectural gap)

Previously one global system_settings row applied to every account — an admin's dark mode change affected agency and personnel logins too
Added theme_key, dark_mode, follow_system, font_key, font_size directly to the users table; each falls back to the app default independently until a user sets their own
New User::appearance() accessor and a new AppearanceSettingController that reads/writes only the authenticated user's own row
Removed the old admin-only admin.settings.* routes; added /settings/appearance (GET/PUT + POST reset) under standard auth middleware, usable by every role
Profile dropdown link renamed "System Settings" → "Appearance" and is now visible to admin, agency, and personnel alike — not admin-only
2) Settings page rework

Font size is now a horizontal slider with tick labels (Small / Default / Large) and a live "current size" readout above it, replacing the button group
Dark mode and "Follow system appearance" now genuinely disable each other's toggle (not just auto-uncheck) — both server-rendered on load and live via JS
Color theme swatches disable the same way while dark mode or follow-system is active, with an inline explanation
Fixed a mobile layout bug: the Font and Text-size sections were forced into a cramped col-6/col-6 row; now col-12 col-md-6 so they stack cleanly on phones
3) Desktop sidebar collapse

Added a collapse/expand toggle button on desktop (icon rail), independent from the existing mobile hamburger/off-canvas behavior so the two can't interfere with each other
State persists via localStorage and applies before first paint to avoid a flash of the wrong layout
4) Logout / "Page Expired" / cross-role permission bug — root cause found

The existing PreventBackHistoryCache ("no-cache") middleware was only wired to guest/login routes, never to the authenticated admin/agency/personnel routes — so an authenticated page could be served from the browser's back/forward cache with a stale CSRF token, producing "Page Expired" on logout
Added no-cache to all four authenticated route groups (admin, agency, personnel, shared web)
Added a client-side pageshow listener that force-reloads a bfcache-restored page as a second layer of defense
Documented (not a code bug): testing multiple roles in different tabs of the same browser shares cookies — logging in elsewhere silently swaps the session for every open tab of that browser. Use separate browser profiles or incognito windows per role instead
5) Dark mode "eye fatigue" fix

Found the cause: dashboard.blade.php (shared by all three roles) hardcoded its own KPI card background (#fff) and pastel icon colors (
#fff4e0, 
#e7f8ee, 
#fde8ea) with no dark-mode awareness at all
Replaced with dark-aware, translucent-tinted versions that blend into the dark surface instead of sitting on top of it as bright pastel squares
Audited the rest of the views for the same pattern — no other page had hardcoded colors, so this was the one fix needed here
6) Security hardening

Found /debug-session, a route publicly leaking the live session ID and CSRF token as unauthenticated JSON — restricted to local development only (APP_ENV=local), inert on Hostinger
Deployment

Migration required: theme_key, dark_mode, follow_system, font_key, font_size added to users
Run php artisan migrate, php artisan route:clear, php artisan view:clear, php artisan config:clear
Manually delete two now-unused files (zip extraction can't remove files): resources/views/admin/settings/index.blade.php and app/Http/Controllers/Admin/SystemSettingController.php
Files changed: 11 (app/Models/User.php, new app/Http/Controllers/AppearanceSettingController.php, new migration, public/css/public.css, resources/views/components/profile-menu.blade.php, resources/views/layouts/app.blade.php, new resources/views/settings/appearance.blade.php, routes/admin.php, routes/agency.php, routes/personnel.php, routes/web.php)

