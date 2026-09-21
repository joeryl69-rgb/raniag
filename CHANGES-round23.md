RANIAG changelog — round 23 (Report wizard Next + UI alignment)

Date: 2026-09-21

Field follow-up after round 22: the public report form stayed on step 1
(Type) even after selecting a type and meeting the 10-character
description. Admin QR / hazard pages also used generic Bootstrap cards
and duplicated success flashes.

Commit on `main` (this round):
  pending until push — Report wizard Next in step header; align QR/hazard cards.

HOW TO APPLY
Push `main` (GitHub Actions deploy). Hard-refresh /report so public-report.js
is not served from an old service-worker cache.

=====================================================================
1. Report wizard actually advances
=====================================================================
Problem: Back/Next lived at the bottom of the form. `.rg-shell { overflow: clip }`
prevents sticky-bottom from staying on screen, so reporters never saw Next.

- Back + Next now sit in the step header (always visible on step 1)
- Extra Next at the footer of the current pane
- Step dots go back to completed steps
- Enter in a one-line field advances the wizard instead of submitting
- Hidden Submit until the last step (unchanged)

Files:
  resources/views/public/report/create.blade.php
  public/js/public-report.js

=====================================================================
2. Admin QR + hazard — same card language as Incidents
=====================================================================
- raniag-card / raniag-card-header on QR create, list, print cards
- same on hazard zone, centers, evacuee panels
- Drop duplicate “QR poster created” alert (layout already flashes session success)
- Hazard layout already flashes session success — removed second copy

Files:
  resources/views/admin/qr_posters/index.blade.php
  resources/views/admin/hazard/index.blade.php

=====================================================================
Verify after deploy
=====================================================================
1. /report — Next is in the top bar; Type + 10+ char description → Location
2. Back returns; dots jump to earlier steps
3. Submit only on step 4
4. /admin/qr-posters — one success banner; create/edit/delete
5. /admin/hazard — same card chrome as Incidents; public map link still works
