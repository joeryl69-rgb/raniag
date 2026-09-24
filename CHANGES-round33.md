# RANIAG — Round 33 Changes

Follow-up rework after round 32 didn't land as expected on the live site and
several requirements were re-scoped based on direct feedback. This round
fixes the actual root cause of "nothing is really happening" (a missing
Mapbox token in production, silently disabling every live route), reverses
the Actions Taken removal, fully removes the per-incident-type Resolution
checklist, and applies a genuine visual restyle (not just reordering) to the
Case Action Control panel, the staff Incident Case File pages, the public
tracking page, and the admin dashboard.

=====================================================================
1. Root cause: live routing was silently broken in production
=====================================================================
The route line + distance/ETA on the case-file map, the public tracking
map, and the "Route to nearest center" hazard-map panel all depend on a
Mapbox access token (`MAPBOX_ACCESS_TOKEN`). The token was set locally but
never present on the Hostinger server's `.env`, so every route request
failed with "Could not load Mapbox route" — the map still showed dots, but
never drew the actual path or ETA, which is why field tracking looked
static no matter what UI changes were made around it.

Fix: `.github/workflows/deploy.yml` now syncs `MAPBOX_ACCESS_TOKEN` from a
GitHub Actions secret into the production `.env` on every deploy
(idempotent `sed`/append — it does not touch any other `.env` values).

  HOW TO APPLY (required, one-time):
  - In GitHub: Settings → Secrets and variables → Actions → New repository
    secret → name `MAPBOX_ACCESS_TOKEN`, value = your Mapbox public token
    (pk.*).
  - Next push to `main` will write it into the server's `.env` automatically.

=====================================================================
2. "Actions Taken" restored
=====================================================================
Round 32 removed this field; it's back exactly as it was — required
alongside Resolution Summary in the agency/personnel resolution form, the
review-before-submit modal, the edit-resolution modals (agency, personnel,
admin), request validation, `ResolutionService`, and the PDF export.

  Files: resources/views/{agency,personnel,admin}/incidents/show.blade.php,
  resources/views/admin/reports/single_pdf.blade.php,
  app/Http/Requests/Agency/SubmitResolutionRequest.php,
  app/Services/ResolutionService.php,
  app/Http/Controllers/{Agency,Personnel,Admin}/ResolutionController.php

=====================================================================
3. Resolution checklist removed entirely
=====================================================================
The per-incident-type checklist ("Scene secured", "Fire suppressed",
"Traffic controlled", etc. — from `incident_types.resolution_checklist`,
introduced in an earlier round) was never part of the real MDRRMO process.
It's now removed completely:
  - No longer rendered/required on the agency/personnel resolution form.
  - Removed from Admin → Incident Types (editor field + JS wiring).
  - Removed from the `IncidentType` model's fillable/casts.
  - New migration drops the `resolution_checklist` column.

  Files: resources/views/{agency,personnel}/incidents/show.blade.php,
  resources/views/admin/incident_types/index.blade.php,
  app/Http/Controllers/Admin/IncidentTypeController.php,
  app/Models/IncidentType.php,
  database/migrations/2026_09_24_123713_drop_resolution_checklist_column.php

=====================================================================
4. Case Action Control — real status console, not a button row
=====================================================================
`resources/views/components/responder-field-strip.blade.php` was rebuilt:
  - Visual phase stepper (Accepted → En route → On scene) with connecting
    line, done/current states — replaces the flat row of identical buttons.
  - Single contextual primary CTA ("Mark 'En route'", "Mark 'On scene'")
    instead of 3 always-visible buttons.
  - The raw "Navigate" button that jumped straight to Google Maps (called
    out as "unexpected, not guaranteed output") is now a small secondary
    "Open in Maps" link — the primary experience is the in-page live route
    on the case-file map, now that Mapbox is actually wired up (see #1).
  - SMS-to-reporter moved into a collapsible drawer instead of always-open.
Case Action Control cards (agency/personnel/admin) now use a new
`.rg-console-card` treatment (blue gradient header, rounded shell) instead
of a plain white Bootstrap card, and primary actions (Accept & Acknowledge,
Resolve Incident) float in a sticky bottom bar on mobile so they're always
reachable without scrolling.

=====================================================================
5. Case-file / tracking / dashboard visual restyle
=====================================================================
New shared design tokens added to `public/css/public.css` (`.rg-app-card`,
`.rg-console-card`, `.rg-field-console`, `.rg-phase-stepper`, `.rg-sticky-cta`,
`.track-agency-avatar`) and applied across:
  - Agency/Personnel/Admin Incident Case File pages — elevated card shadows,
    consistent rounded chrome instead of stock Bootstrap cards.
  - Public tracking page — same elevated-card treatment plus a colored
    icon-avatar per responding agency row.
  - Admin dashboard — Situational Map toolbar restyled with a blue gradient
    header consistent with the Case Action Control console, KPI cards get a
    hover lift, card shadows deepened for a more "app" feel.

=====================================================================
6. New-incident alert — fixed a real gap, not just cosmetic
=====================================================================
The pulsing red banner logic previously only "primed" its baseline on the
very first poll after a page load — meaning a dispatcher who opened the
dashboard right after a report came in would never see the alert for that
report. It now checks the latest incident's `reported_at`: if it's within
the last 2 minutes and unseen, the banner (+ tone) fires immediately on
first load instead of only for incidents that arrive *after* the tab has
been open for a full poll cycle.

  File: resources/views/dashboard.blade.php

=====================================================================
Full file list
=====================================================================
.github/workflows/deploy.yml
app/Http/Controllers/Admin/IncidentTypeController.php
app/Http/Controllers/Admin/ResolutionController.php
app/Http/Controllers/Agency/ResolutionController.php
app/Http/Controllers/Personnel/ResolutionController.php
app/Http/Requests/Agency/SubmitResolutionRequest.php
app/Models/IncidentType.php
app/Services/ResolutionService.php
database/migrations/2026_09_24_123713_drop_resolution_checklist_column.php
public/css/public.css
resources/views/admin/incident_types/index.blade.php
resources/views/admin/incidents/show.blade.php
resources/views/admin/reports/single_pdf.blade.php
resources/views/agency/incidents/show.blade.php
resources/views/components/responder-field-strip.blade.php
resources/views/dashboard.blade.php
resources/views/personnel/incidents/show.blade.php
resources/views/public/track/show.blade.php

=====================================================================
Verify after deploy
=====================================================================
1. Add the `MAPBOX_ACCESS_TOKEN` GitHub Actions secret (see #1) — without
   this, routing will still silently fail on the live server.
2. Open an agency/personnel case file with an active assignment: confirm
   the phase stepper renders, "Mark 'En route'" advances it, and the live
   map draws an actual route line with distance/ETA (not just dots).
3. Submit a resolution: confirm both Resolution Summary and Actions Taken
   are required, and there is no "Resolution checklist" section anywhere.
4. Admin → Incident Types → edit a type: confirm there is no "Resolution
   checklist" field.
5. Open the public tracking page for an incident with assignments: confirm
   the map isn't stretched on desktop and the "Responding agencies" panel
   shows phase badges with avatar icons.
6. On the dashboard, submit a brand-new report from another tab/device,
   then load the dashboard fresh: the red pulsing alert should appear
   immediately (within one ~30s poll) instead of requiring a prior visit.
