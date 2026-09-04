# RANIAG — Round 6: filter alignment, pagination, animation, icon consistency, timeline visibility, real-time updates, PDF spacing

30 files, extract into your project root (overwrite).

## 1. Feedback filter didn't match its own data
The filter bar used unlabeled auto-submit dropdowns with no search box, unlike every other list page. Rebuilt to the app's standard pattern (labeled Search/Status/Category/Source/From/To + explicit Filter/Clear buttons), and added the missing `q` search (subject, message, name, email) to `FeedbackController`, plus date-range filtering it never had.

## 2. Pagination not centralized
4 pages (Feedback, Announcements, Agency Archived Reports, Notifications) used Laravel's default Tailwind paginator while the other 7 paginated pages use Bootstrap 5. Since the app has no Tailwind compiled in, those 4 pages' controls rendered unstyled. Fixed all 4 to use `pagination::bootstrap-5` with the same `hasPages()` guard as the rest of the app.

## 3. Landing-page reveal animation not centralized
The `data-rg-reveal` fade-in-on-scroll pattern (shared IntersectionObserver already in `layouts/public.blade.php`) was only wired into 3 of 9 public pages. Added it to the real landing page (`public/home.blade.php` — hero, feature cards, updates, download CTA, support CTA), plus Community Dashboard, Support Center, Report Success, and Offline.

## 4. Incident icon color mismatch (orange vs red) — found and fixed
Root cause: `public/js/incident-map-icons.js` force-overrode a map marker's **fill** color to orange whenever an incident was flagged "outside jurisdiction" — clobbering the incident type's real color (e.g. Fire's red) — even though a separate orange **ring/border** already exists for exactly that purpose (`.is-outside` in `public/css/public.css`). Removed the fill override. Markers, list badges, and detail-page badges now all pull from the same source (`$incidentType->color`/`icon`), so a type's color is identical everywhere; "outside AOR" is indicated only by its ring, as designed.

## 5. "Awaiting info" was visible to the public + no status indicators
The `status_updates` table already had an `is_public` column, but it was never used — every status change was hardcoded `isPublic: true`, including internal "pending info" requests (which can contain internal notes not meant for reporters).
- Added `Incident::publicTimeline` — a filtered accessor that excludes any update where `is_public === false`.
- Switched the public tracking page's Full History to use `publicTimeline` instead of `statusTimeline`.
- Fixed the Agency and Personnel status-update controllers so a `pending_info` transition is now recorded as `isPublic: false` — it still shows up (in general terms) at the top of the tracking page via the existing plain-language status message, but the granular history entry (and any internal note) no longer appears in Full History.
- Color-coded status dots already existed in CSS but were scoped to the public page only; extended to the staff-side (admin/agency/personnel) incident detail timelines by adding `data-status="..."` and the `raniag-timeline-wide` class.
- Added the updater's **agency name** next to their name on staff timelines (`{{ $update->user->agency->name }}`), for clarity on which agency made each update.

## 6. Real-time updates system-wide (no manual reload)
Built a small reusable client-side module, `public/js/live-refresh.js`, loaded app-wide in `layouts/app.blade.php`, following the same poll pattern the notification bell already uses:
- Any list region opts in with `data-live-refresh data-live-refresh-target="#some-id"` on a wrapper and a matching `id` on the element to swap.
- Polls the current page URL (filters/query string preserved) every 15s by default, diffs the target region's HTML, and swaps it in place only if it changed — no full reload, scroll position and open dropdowns are undisturbed.
- Simultaneous polls to the same URL are deduplicated so multiple live regions on one page don't multiply server load.
- Wired into every list-style page across all 3 portals: Admin/Agency/Personnel incident dispatch queues, Admin/Agency Document Requests, Admin SMS Logs, Admin Audit Logs, Admin Agencies + Personnel Accounts tables, Admin Case Documents Repository, Admin Announcements, Agency Archived Reports, and the Feedback & Concerns list.
- No controller changes were needed — this reads the same Blade-rendered HTML the page already returns; it doesn't require a JSON API.

## 6b. Filter audit across the app
Went through every page with a filter form (agencies, audit logs, sms logs, document requests admin/agency, incident lists admin/agency/personnel, archived reports, incident documents, notifications). Checked each dropdown/select's options against the actual controller query logic and underlying enum/data values. Feedback (item 1) was the only page where the filter didn't line up with the data; everywhere else the dropdown options are generated dynamically from the same source the controller filters against (enums, distinct DB values), so they're already correct and didn't need changes.

## 7. PDF report image spacing
`resources/views/admin/reports/single_pdf.blade.php`: evidence/case-document photos were stacked one-per-row at up to 300px tall despite the CSS class being named `.evidence-grid` (dompdf doesn't support real CSS grid/flex reliably). Changed to a dompdf-safe 2-per-row `inline-block` layout, reduced max image height to 180px, and added `page-break-inside: avoid` so a single photo+caption is never split across a page boundary. This should meaningfully cut down on forced page breaks in longer reports.

---

## What to do next
1. Extract this zip into your project root (30 files; overwrite).
2. `php artisan route:clear && php artisan view:clear && php artisan config:clear`
3. Feedback & Concerns: try the new Search box and date range — confirm results match what you type.
4. Any paginated list with 2+ pages (e.g. Feedback, Notifications): confirm the pager now looks like the rest of the app (Bootstrap style, not plain blue links).
5. Load the real landing page (`/`) and scroll — sections should fade/slide in like the tracking page does. Also check Community Dashboard, Support Center, Report Success, Offline.
6. Open an incident with an "outside AOR" flag on the map — its pin should now show the incident type's real color (e.g. red for Fire) with an orange ring, not an all-orange pin.
7. On an assigned incident, use "Request More Info" (pending_info) as agency/personnel, then check that incident's **public** tracking page — the granular "Awaiting information..." entry should NOT appear in Full History (the general "we need more info" message at the top still does). Then check the **staff-side** detail page — you should see the entry there, color-coded orange, with the responding agency's name.
8. Open any incident dispatch queue (Admin/Agency/Personnel → Incidents) in two browser tabs, change a status in one, and watch the table in the other update within ~15s without reloading. Same for Document Requests, SMS Logs, Audit Logs, Agencies, Case Documents, Feedback.
9. Generate a single-incident PDF report for an incident with several evidence photos and confirm they're laid out 2-per-row and no longer forcing extra pages.
