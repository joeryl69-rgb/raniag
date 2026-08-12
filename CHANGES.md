# RANIAG Update — Files Changed

Extract this zip directly into your project root (mirrors app/ and resources/,
overlaying only the files below — nothing else in your project is touched).

## Part 1 — Filter rework (search + functional date range, no future dates)
- app/Support/Filters.php — search()/dateRange() helpers; dateRange() clamps to today server-side
- resources/views/components/filters/toolbar.blade.php — reusable filter form, date inputs capped at today
- Incidents (Admin/Agency/Personnel): IncidentRepository.php + 3 controllers + 3 index views
- Admin Agencies/Accounts (previously zero filters): AgencyController.php + agencies/index.blade.php
- SMS Logs & Audit Logs (previously zero filters): Admin/DashboardController.php + sms-logs/audit-logs views
  — Audit Logs also gets a dedicated Log Name dropdown (was only reachable via text search before)
- Incident Documents + Agency Archived Reports: redundant "year" dropdowns replaced with date range
- Admin + Agency Document Requests: search + date range added

## Part 2 — Outside AOR workflow (new)
Addresses the gap where a report pinned outside Pamplona's jurisdiction had no distinct
outcome: admins could only approve/assign or reject, and the public tracker had no way
to show "referred elsewhere" as a final state — it would otherwise look stuck.

- app/Enums/IncidentStatus.php — new terminal status `outside_aor` ("Outside AOR (Referred)")
- app/Http/Requests/Admin/ValidateIncidentRequest.php — accepts `outside_aor` action,
  requires referral notes (which agency/municipality it went to) when chosen
- app/Http/Controllers/Admin/IncidentController.php — handles the new action, records a
  public status update (visible on tracking), skips agency assignment entirely
- resources/views/admin/incidents/show.blade.php — new "Mark as Outside AOR" option in the
  Action Dispatcher, notes become required client-side when selected, and a hint appears
  automatically when the pin is already flagged outside municipality limits
- resources/views/components/public/status-badge.blade.php — distinct badge color for the new status
- resources/views/public/track/show.blade.php — tracker now shows a clear final "Referred to
  Another Agency/Municipality" card (same pattern as Rejected) instead of a stepper stuck mid-progress

## Not touched
- Personnel\DocumentRequestController — no index/list page exists on this controller.

## Note
PHP isn't available in this build environment, so all files were verified via brace/paren
balance and manual review of every match()/enum usage for exhaustiveness, not php -l or a
live Laravel boot. Run `php artisan view:clear` after extracting, then:
1. Submit a test report with a pin outside Pamplona, open it as admin, choose "Mark as Outside AOR".
2. Confirm the reporter's tracking page shows the new referred/final state, not a stuck stepper.
3. Click through the filter pages listed in Part 1 as before.

## Part 3 — Map marker consistency (new)
The incident detail map used Leaflet's plain default pin, inconsistent with the colored
icon-pins already used on the dashboards.

- resources/views/admin/incidents/show.blade.php
- resources/views/agency/incidents/show.blade.php
- resources/views/personnel/incidents/show.blade.php

All three now render the same colored, icon-based pin style as the dashboard maps
(incident type color/icon), and turn orange with an "Outside AOR" popup label when the
pin is outside Pamplona's jurisdiction — visually flagging that case everywhere, not just
via the text banner.
