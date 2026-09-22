RANIAG changelog — round 24 (OTP consistency, reporting evidence gate, QR poster
redesign, hazard mapping UX rework)

Date: 2026-09-22

Four field-reported items from the live system: OTP asking again after every
logout even on the same device, reporters able to stay anonymous with zero
evidence and zero way to reach them, QR posters that look like a generic
third-party image with no branding and no download, and a hazard mapping
page that crammed five CRUD forms and two tiny maps onto one screen.

Commit on `main` (this round):
  pending until push — see files list per section below.

HOW TO APPLY
Extract this zip over the existing project root (mirrors `raniag/`), then:
  php artisan migrate --force   (no new migrations this round — schema unchanged)
  php artisan view:clear && php artisan config:clear
Push `main` (GitHub Actions deploy). Hard-refresh /report, /admin/qr-posters
and /admin/hazard so the updated JS isn't served from a stale cache.

=====================================================================
1. OTP inconsistency — "trust this device" is now automatic
=====================================================================
Root cause: "Trust this device" was an opt-in checkbox that defaulted
UNCHECKED. Anyone who didn't notice or untick it got OTP'd on every single
login regardless of logging out and back in on the same browser — that's
what read as "random."

Fix: trust is now automatic on every successful OTP verification. No
checkbox, no coin-flip. Still fully overridable for kiosk/shared devices —
set `RANIAG_TWO_FACTOR_TRUSTED_DAYS=0` and the app stops issuing trusted
cookies entirely (previously `trusted_device_days` was floored at 1 and
couldn't disable trust at all).

Files:
  app/Services/TwoFactorService.php           (new trustedDeviceEnabled())
  app/Http/Controllers/Auth/TwoFactorChallengeController.php
  resources/views/auth/two-factor-challenge.blade.php

=====================================================================
2. Reporting evidence gap — anonymous now requires a photo or GPS capture
=====================================================================
Root cause: `is_anonymous` defaulted to checked and `reporter_phone` /
`reporter_email` were always optional — a reporter could submit with no
photo, no GPS capture, and no way for MDRRMO to reach them or verify the
report at all.

Fix, server-side (source of truth): `StoreIncidentReportRequest::hasEvidence()`
checks the uploaded `evidence` files and the GPS-camera capture log. With no
evidence, `is_anonymous` is forced off in `prepareForValidation()` and at
least one of phone/email becomes `required_without` the other. With
evidence, anonymous stays fully optional — unchanged from before.

Fix, client-side (so this shows as a friendly heads-up, not a server error
after the fact):
- `is_anonymous` checkbox now defaults UNCHECKED instead of checked
- Leaving the Evidence step with nothing attached pops a one-time modal
  explaining why, then the Contact step disables the anonymous toggle,
  marks phone/email with a required asterisk, and shows an inline banner
- `gps-camera.js` now fires `raniag:evidence-changed` whenever a capture is
  added or removed, so the gate reacts live if the reporter goes back and
  adds a photo
- A validation-error redirect now lands the reporter on the step that
  actually has the error (previously always reset to step 1)

Files:
  app/Http/Requests/Public/StoreIncidentReportRequest.php
  resources/views/public/report/create.blade.php
  public/js/public-report.js
  public/js/gps-camera.js

=====================================================================
3. QR posters — MDRRMO-branded, one-per-page print, real download
=====================================================================
Root cause: QR images came from a third-party image API
(`api.qrserver.com`) with no logo, and the print layout was a small
multi-per-page card grid with no download option.

Fix: QR codes now render entirely client-side (qrcodejs, high error
correction) with the MDRRMO seal composited into the center — the extra
error-correction budget is exactly what makes covering part of the code
with a logo still scan reliably. Each poster got a proper letterhead header
(same navy branding as the PDF reports), prints one-per-page via CSS page
breaks instead of a cramped grid, and has a working "Download PNG" button
that exports the branded QR straight from the canvas.

`QrPoster::qrImageUrl()` (the third-party dependency) is removed — nothing
else referenced it.

Files:
  app/Models/QrPoster.php
  resources/views/admin/qr_posters/index.blade.php
  public/js/qr-poster.js (new)

=====================================================================
4. Hazard mapping — tabbed layout, bigger maps, evacuee registry visible
=====================================================================
Root cause: five CRUD forms (zone, zone list, center, evacuee, center list)
and two 320px maps were all stacked on one screen at once. Also found:
`$evacuees` was already being queried by the controller but never rendered
anywhere in the view, and there was no way to check an evacuee back out
once registered.

Fix: reorganized into three tabs — Hazard Zones, Evacuation Centers,
Evacuee Registry — each with a full-width 440px map next to its form, one
task on screen at a time. Added a 4-up stat strip at the top (zones,
active zones, centers open, evacuees checked in). Added the missing
evacuee registry table (name, center, age/sex, check-in time, status) with
a working Check out action.

Per the round 21 decision, live-route/dispatch and Mapbox-for-mobile stay
deferred — this round didn't touch that, only the visual/UX layout.

New route: PATCH /admin/hazard/evacuees/{evacuee}/check-out

Files:
  resources/views/admin/hazard/index.blade.php
  app/Http/Controllers/Admin/HazardEvacController.php (checkOutEvacuee)
  routes/admin.php

=====================================================================
Verify after deploy
=====================================================================
1. Login as admin/agency → OTP once → logout → log back in on the same
   browser within the trusted-device window → no OTP prompt
2. Set RANIAG_TWO_FACTOR_TRUSTED_DAYS=0 → OTP required every login
3. /report — Evidence step with nothing attached → Next → popup appears →
   Contact step: anonymous toggle disabled, phone/email marked required
4. /report — attach a GPS photo → Contact step: anonymous toggle enabled
   again, no required marks
5. /report — submit with no evidence and no contact info → server 422,
   redirect lands back on the Contact step (not step 1)
6. /admin/qr-posters — QR renders with MDRRMO seal in the center, scans
   correctly to the barangay-prefilled report form, Download PNG works,
   Print shows one full poster per page
7. /admin/hazard — three tabs load, both maps render at full size on tab
   switch, Evacuee Registry tab lists checked-in evacuees, Check out works
