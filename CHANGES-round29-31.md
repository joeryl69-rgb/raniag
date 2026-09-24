# RANIAG changelog — rounds 29–31 (unified hazard/risk map, live Mapbox
dispatch & tracking, SMS/email tracking deep links)

Date: 2026-09-24

Implements the phased situational mapping plan on top of Round 28 Mapbox
hazard routing. Ships as one deployable set so Hostinger picks up the full
rework after push to `main`.

HOW TO APPLY
Push `main` (or wait for GHA after merge). Ensure Hostinger `.env` has:
  MAPBOX_ACCESS_TOKEN=pk.…
  MAPBOX_STYLE=mapbox/streets-v12
  MAPBOX_DIRECTIONS_PROFILE=walking
  PHILSMS_API_TOKEN=…
  MAIL_* configured
Then `php artisan config:cache` / `optimize:clear` (GHA may already).
Hard-refresh `/hazard-map`, `/track`, admin dashboard, and incident show.

Live site:
  https://mediumorchid-weasel-407759.hostingersite.com

=====================================================================
Round 29 — Unified hazard + limited public risk + Mapbox shared stack
=====================================================================
- Shared `public/js/raniag-mapbox.js` (basemap + Directions helpers)
- `SituationalMapService` builds public risk awareness (barangay open-count
  choropleth, **no exact incident coordinates**) and staff hazard layers
- Public Live Map (`/hazard-map`): Risk awareness layer + zones + centers +
  My location route; nav label **Live Map**
- Admin dashboard situational map: Mapbox tiles (when token set), hazard
  zones + evac centers overlaid with open incident pins; `hazard_zones`
  meta on pins; corrected Pamplona map center
- Admin hazard draw/pick maps use Mapbox when configured

Files (key):
  app/Services/SituationalMapService.php
  app/Http/Controllers/Public/HazardMapController.php
  app/Http/Controllers/Admin/DashboardController.php
  public/js/raniag-mapbox.js
  public/js/public-hazard-map.js
  resources/views/public/hazard/map.blade.php
  resources/views/dashboard.blade.php
  resources/views/admin/hazard/index.blade.php
  tests/Feature/Public/HazardMapTest.php

=====================================================================
Round 30 — Live dispatch + tracking Mapbox routes
=====================================================================
- Agency + personnel GPS ping via `raniag-location-ping.js` (faster while
  en_route / on_scene)
- Staff live-units JSON: `/{role}/incidents/{id}/live-units`
- Admin / agency / personnel incident maps: live unit markers + Mapbox
  driving route unit → scene
- Public track page: live response map + `GET /track/{tn}/units` (session
  gated); agency label only (no personal names/phones)
- Hazard containment chips on admin incident map when `meta.hazard_zones`

Files (key):
  public/js/raniag-location-ping.js
  public/js/raniag-dispatch-map.js
  public/js/public-track-map.js
  app/Http/Controllers/Shared/LiveUnitsController.php
  app/Http/Controllers/Public/IncidentTrackController.php
  routes/admin.php, agency.php, personnel.php, public.php
  resources/views/admin|agency|personnel/incidents/show.blade.php
  resources/views/public/track/show.blade.php
  tests/Feature/Public/LiveUnitsAndRiskTest.php

=====================================================================
Round 31 — SMS/email deep links + submit alerts
=====================================================================
- After non-anonymous submit with email/phone: PhilSMS and/or email with
  one-tap tracking URL (`/track?tracking_number=…`)
- Status-update SMS/email include the same deep link + CTA button
- New `IncidentReportReceivedMail` + updated status-update template

Files (key):
  app/Services/NotificationService.php
  app/Services/IncidentService.php
  app/Mail/IncidentReportReceivedMail.php
  app/Mail/IncidentStatusUpdateMail.php
  resources/views/emails/incidents/report-received.blade.php
  resources/views/emails/incidents/status-update.blade.php
  tests/Feature/ReporterNotifyDeepLinkTest.php

=====================================================================
Verify after deploy
=====================================================================
1. `/hazard-map` → Risk awareness choropleth; no exact report pins; My
   location still routes to nearest open center.
2. Admin dashboard → Mapbox basemap (with token) + hazard polygons +
   open incident pins.
3. Assign agency → agency incident show pings GPS → track page shows
   approaching unit + route after verified lookup.
4. Submit report with phone/email → SMS/email with track link; anonymous
   submit skips reporter notify.
5. Hard-refresh if CSS/JS stale; clear Hostinger cache if needed.
