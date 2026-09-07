Note: public/css/public.css in this zip supersedes the one from the filter-bar patch — extract this one last, in order, and it overwrites correctly.

This patch is what makes the revamp reach every role:

public/css/public.css — table row hover (accent bar), status/priority badge weight, and the empty-state icon+message pattern already used on 8+ list pages, all upgraded in one shared file. Since public.css loads in every layout (admin, agency, personnel, and the public tracking page), this reaches all of them with zero page-by-page edits.
resources/views/components/priority-badge.blade.php (new) — the priority-badge markup was byte-for-byte duplicated across admin/agency/personnel incident tables; extracted into one shared component, same pattern your codebase already used for <x-public.status-badge>.
3 incident index files — swapped the duplicated inline block for <x-priority-badge>.
Final changelog — everything applied, in order

1. raniag-patch-performance-rings.zip

Fixed: admin Situational Map showed resolved/closed/rejected incidents indefinitely (query had no status filter) and capped at the last 10 reports. Now shows all currently-open incidents only.
Added: 3 circular Performance Overview rings on the admin dashboard — Resolution Rate, Dispatch Coverage, SLA Compliance — driven by real DB numbers, admin-only.
Files: config/raniag.php, app/Http/Controllers/Admin/DashboardController.php, resources/views/dashboard.blade.php

2. raniag-patch-filter-bar.zip

Rebuilt the shared filter toolbar with Alpine.js: active-filter chips (removable individually), auto-submit on select/date change, debounced live search, clear-all.
Migrated audit-logs, feedback, and document_requests (admin) off ad-hoc forms onto the shared component.
Discovered and fixed: Alpine.js was never actually loaded on any admin/agency/personnel page (only on the guest/login layout) — fixed via CDN include, which also revives the existing dropdown/modal components that were silently inert.
Files: resources/views/components/filters/toolbar.blade.php, public/js/filter-bar.js, resources/views/layouts/app.blade.php, public/css/public.css, 3 migrated views

3. raniag-patch-filter-bar-agencies.zip

Extended the toolbar with configurable field names so two independent filter forms can share one page (agencies + personnel tabs) without query-string collisions. Migrated both.
Files: resources/views/components/filters/toolbar.blade.php (updated again), resources/views/admin/agencies/index.blade.php

4. raniag-patch-tables-badges.zip (this one)

Shared table/badge/empty-state polish in public.css — reaches admin, agency, personnel, and the public tracking page automatically.
Extracted the duplicated priority-badge logic (identical in 3 files) into <x-priority-badge>.
Files: public/css/public.css (final version), resources/views/components/priority-badge.blade.php, 3 incident index views

Apply all 4 in the order above, then php artisan config:clear && php artisan view:clear and hard-refresh once (Alpine now loads from CDN).

Deliberately not touched, with reasons on record:

agency/document_requests/index.blade.php — different compact inline filter layout, risky to convert without visual verification.
The security/architecture findings from our first review (evidence files on the public disk, unused policies, APP_DEBUG=true, QUEUE_CONNECTION=sync) — those were advisory, not code changes, and are still open on your end unless you applied the fixes yourself.
Full framework migration — never in scope; everything here works within Blade + Bootstrap + Alpine, which is what made drop-in patches possible instead of a rewrite.