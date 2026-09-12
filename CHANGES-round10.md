RANIAG changelog — Round 10

Overview
This update fixes the mobile dock, avatar, and notification-bell styling that silently failed to load; makes dark mode and "follow system appearance" actually work; rebuilds the settings toggles; adds font controls; fixes push notifications reverting after being allowed; and moves System Settings into the profile menu.

1) Root style-loading bug (mobile dock, bell, avatar)

Found the actual cause: these three components pushed their CSS via @push('styles'), but render after @stack('styles') already prints in <head> — so the CSS was silently dropped on every page
Converted to inline <style> blocks so they always load regardless of render order
This alone fixes the "floating dock is just plain text," "dark mode toggle looks unstyled," and "profile button isn't a circle" issues

2) Real "Follow system appearance"

Previously just copied your OS preference into the global dark-mode flag once, at save time — not an ongoing sync
Added a follow_system column; when enabled, a small script in <head> matches each visitor's own OS theme on every page load, independent of what anyone else saved

3) Dark mode fixes

Fixed invisible text/icons on .btn-outline-dark (sidebar hamburger), dropdown items, plain links, and labels that the dark-mode CSS block never covered
Added dark backgrounds/borders for the mobile dock, bell button, and avatar button so they don't stay light-mode white in dark mode

4) System Settings rework

Rebuilt the toggle switches from scratch (appearance:none + custom thumb) — the old ones were being overridden by Bootstrap's own switch styling and rendering as plain circles
Added Font family (4 options) and Text size (small/default/large) controls, applied globally via CSS variables like the color themes already are
Moved "System Settings" out of the sidebar into the profile avatar dropdown (admin-only), loosely modeled on a Facebook-style account menu

5) Push notifications

subscribe() had no error handling — a failure after you hit "Allow" silently reverted the toggle to off with no explanation
sw.js used an atomic cache.addAll() where one failed asset killed the entire service-worker install, which made the browser hang indefinitely waiting for a worker that never activated
Fixed both: per-asset caching that can't be taken down by one bad URL, plus real error messages when something does fail

6) Deployment

Migration required: follow_system, font_key, font_size columns added to system_settings
Run php artisan migrate, php artisan view:clear, php artisan config:clear after extracting
Hard-refresh or unregister the old service worker once, since sw.js's cache version changed

Files changed: 12 (app/Models/SystemSetting.php, app/Support/ThemePresets.php, app/Http/Controllers/Admin/SystemSettingController.php, 1 new migration, public/css/public.css, public/js/push-notifications.js, public/sw.js, and 5 Blade views)