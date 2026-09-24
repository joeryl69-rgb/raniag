# RANIAG changelog — round 32 (dispatch UX overhaul: accept modal,
map-first case files, live tracking panels, dashboard alert)

Date: 2026-09-24

Builds on Rounds 29–31 live Mapbox dispatch/tracking. This round makes
field workflows **actionable for real use**: confirmation before accept,
map-first incident case files, fixed public track map aspect ratio with
responding-agency status, map-first admin Command Center with filters,
and a pulsing new-incident alert on open dashboards.

Commit on `main`:
  a7026e6  Make dispatch and tracking workflows real for field use.

=====================================================================
HOW TO APPLY
=====================================================================
Push already on `main` (a7026e6). Wait for GHA → Hostinger, or pull on
the server. Then:
  php artisan optimize:clear
  php artisan view:clear
Hard-refresh agency/personnel case file, `/track`, and admin dashboard.
Clear Hostinger website cache if CSS/JS looks stale.

Live site:
  https://mediumorchid-weasel-407759.hostingersite.com

No new `.env` keys required for this round (Mapbox / PhilSMS / mail
unchanged from Rounds 29–31).

=====================================================================
1. Accept & Acknowledge — confirm modal first
=====================================================================
Agency and personnel no longer submit accept with a bare button click.
**Accept & Acknowledge** opens a Bootstrap modal summarizing:
  category, priority, location, reported time, tracking #, reporter
  (when available), short description.
**Confirm & Accept** posts the existing accept route.

Reusable component:
  resources/views/components/confirm-action-modal.blade.php

Wired in:
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php

=====================================================================
2. Resolution — remove Actions Taken
=====================================================================
The **Actions Taken** textarea was not part of the prior field process
and is removed from create/edit resolution UIs (agency, personnel,
admin override). Resolution checklist + summary remain.

Backend:
- `SubmitResolutionRequest` no longer requires `actions_taken`
- Agency / Personnel / Admin resolution controllers stop collecting it
- `ResolutionService` stores `actions_taken` as null when omitted
- DB column kept for old records; PDF shows Actions Taken only if set

Files:
  app/Http/Requests/Agency/SubmitResolutionRequest.php
  app/Http/Controllers/Agency/ResolutionController.php
  app/Http/Controllers/Personnel/ResolutionController.php
  app/Http/Controllers/Admin/ResolutionController.php
  app/Services/ResolutionService.php
  resources/views/agency|personnel|admin/incidents/show.blade.php
  resources/views/admin/reports/single_pdf.blade.php

=====================================================================
3. Incident case file — map-first + live units
=====================================================================
Agency / personnel case file layout reordered for mobile PWA:
  1. Live Response Map (aspect-ratio frame) + unit status strip
  2. Case Action Control (accept modal / field phase / resolve)
  3. Case details, evidence, timeline

Admin incident map uses the same aspect-ratio frame + live unit strip.
`raniag-dispatch-map.js` fits bounds more carefully and invalidates size
on resize so GPS unit markers and routes paint correctly.

Status strip text comes from existing live-units poll (~15s) + Mapbox
drive distance when a token is present.

Files:
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php
  resources/views/admin/incidents/show.blade.php
  public/js/raniag-dispatch-map.js

=====================================================================
4. Public tracking — fixed map + responding agencies
=====================================================================
Track live map no longer uses a full-width 280px strip (looked stretched
on desktop). New `.track-map-frame` uses aspect-ratio + max-width.

**Responding agencies** sheet sits under the map with phase badges:
  Awaiting acceptance / Accepted / En route / On scene
(reuses assignment `field_phase` + acknowledged_at).

`public-track-map.js` fitBounds pad/maxZoom adjusted; ResizeObserver
keeps Leaflet sized inside the new frame.

Files:
  resources/views/public/track/show.blade.php
  public/css/public.css
  public/js/public-track-map.js

=====================================================================
5. Admin / role dashboard — map-first, filters, alert
=====================================================================
Command Center order:
  1. New-incident alert banner (when a newer case arrives)
  2. Situational Map (primary)
  3. KPI strip
  4. Performance rings (admin)
  5. Analytics (without duplicate location cards)

Map filters (admin): status, priority, barangay — client-side filter of
open incident markers. **Top Barangays** chart and **Redundancy
Hotspots** list removed as stacked cards; replaced by a floating
**Area insight** panel (open count + hotspot flag) when a marker is
clicked or a barangay filter is chosen.

New-incident alert:
- `latest_incident` on admin / agency / personnel `dashboard.json`
- localStorage baseline so refresh does not re-alert
- dismissible red-pulse banner + short Web Audio tone
- Open Case link to the incident show route

Files:
  resources/views/dashboard.blade.php
  app/Http/Controllers/Admin/DashboardController.php
  app/Http/Controllers/Agency/DashboardController.php
  app/Http/Controllers/Personnel/DashboardController.php

=====================================================================
Verify after deploy
=====================================================================
1. Agency case file (unaccepted assignment) → Accept opens modal →
   Confirm accepts; field phase strip + live map under header.
2. Resolve form → no Actions Taken field; summary + checklist still work.
3. `/track/{tn}` (verified) → map is square/aspect-framed, not a wide
   thin strip; responding agencies list under map with phase badges.
4. Admin dashboard → map is first; filters hide/show pins; click pin →
   Area insight; new submitted incident while tab open → red banner + tone.
5. Hard-refresh / clear Hostinger cache if blades or public CSS/JS stale.
