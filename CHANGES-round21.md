RANIAG changelog — round 21 (Round 20 UX / functionality fixes)

Date: 2026-09-21

Follow-up to round 20 after field review: round 20 shipped, but several
surfaces were awkward or only partially usable. This round hardens UX
and removes unused friction without rewriting architecture.

Commit on `main`:
  f2a9a84  Improve Round 20 UX: trusted 2FA device, tracking-only lookup,
           draw-based hazard map.

HOW TO APPLY
Push/pull `main` on Hostinger (GitHub Actions deploy runs
`git reset --hard origin/main`, `composer install`, `npm run build`,
`php artisan migrate --force`, optimize caches).

After deploy, confirm the new migration applied:
  php artisan migrate --force
  → 2026_09_21_180000_add_color_to_hazard_zones_table

Optional .env (defaults are fine if omitted):
  RANIAG_TWO_FACTOR_TRUSTED_DAYS=30

=====================================================================
Decisions locked
=====================================================================
- Public track: tracking number alone (no access-code / PIN UX).
- Voice: keep Web Speech dictation; clear Listening vs Idle indicator.
- Maps (web): Leaflet + OSM + Leaflet.draw on admin.
  Mapbox stays deferred for Phase 6 mobile.
- Dispatch Queue page removed (assign remains on incident show).

=====================================================================
1. Trusted device for 2FA
=====================================================================
Problem: Admin/agency had to enter email OTP on every login, including
after logout on the same browser.

- config/raniag.php + .env.example: two_factor.trusted_device_days (30)
- TwoFactorService: issueTrustedDeviceCookie / hasTrustedDevice
  (cookie raniag_trusted_device + cache TTL; no new table)
- AuthenticatedSessionController: skip OTP when trusted cookie valid
- TwoFactorChallengeController + challenge blade: “Trust this device”
  checkbox after successful OTP
- Logout does NOT clear the trusted cookie (Facebook-style)
- Personnel still skip 2FA entirely

=====================================================================
2. Tracking — number only + copy / download image
=====================================================================
Problem: Success/track required two codes (tracking number + access code).

- IncidentService: stop generating/showing tracking PIN (column kept nullable)
- TrackIncidentRequest / IncidentTrackController: lookup by tracking number only
- Success page: single tracking number, Copy, Download Image (canvas PNG)
- Track form: access-code field removed
- tests/Feature/Public/IncidentReportTest.php updated

=====================================================================
3. Public report wizard + speech-to-text
=====================================================================
Problem: Steps felt stuck; voice had no clear listening state.

- public/js/public-report.js: map init try/catch so Leaflet failures
  do not block wizard handlers; inline step errors (not only alert);
  sticky step progress; clearer Next/Back
- Voice: Listening spinner + Idle/Listening status; en-PH; better
  not-allowed / no-speech / network / HTTPS messages
- resources/views/public/report/create.blade.php: wizard error banner,
  voice indicator UI

=====================================================================
4. Dynamic admin QR posters
=====================================================================
Problem: Static “print all barangays” page was not flexible.

- QrPosterController: GET with selected barangays[]
- admin/qr_posters/index: checkboxes, Select all / Clear, Generate,
  Print selected (still uses api.qrserver.com for QR images)

=====================================================================
5. Remove Filipino locale
=====================================================================
- Deleted LocaleController, SetLocale middleware, lang/tl.json
- Unwired from bootstrap/app.php, routes/public.php
- Removed EN/Filipino nav toggle from layouts/public.blade.php
- English __() strings left in place (harmless)

=====================================================================
6. Remove Dispatch Queue page
=====================================================================
- Deleted DispatchQueueController + admin/dispatch/queue view
- Removed admin.dispatch.* routes and sidebar link
- Kept: incident-show assign, AssignmentService, SLA escalate command,
  availability toggles, is_drill column

=====================================================================
7. Hazard zone mapping — draw + color
=====================================================================
Problem: Admins had to paste GeoJSON and type lat/lng by hand.

- Migration: hazard_zones.color (nullable override; falls back to type color)
- HazardZone::displayColor()
- admin/hazard/index: Leaflet + Leaflet.draw polygon/rectangle,
  color picker, click map (or drag marker) for evacuation centers
- Sidebar label: “Hazard zone mapping”
- Public hazard map uses per-zone color when set
- Mapbox not introduced (Phase 6)

=====================================================================
Verify after deploy
=====================================================================
1. Admin/agency login → OTP → check Trust this device → logout →
   login again without OTP on same browser
2. Submit report → success: Copy + Download Image; /track with number only
3. /report wizard Next/Back; Voice shows Listening indicator (Chrome + HTTPS)
4. Admin → QR Posters: select 1–2 barangays → Generate → Print
5. No Filipino toggle; no Dispatch Queue in sidebar
6. Admin → Hazard zone mapping: draw polygon, pick color, place center;
   public /hazard-map shows colored zones

=====================================================================
Out of scope (unchanged)
=====================================================================
- Mapbox / native mobile mapping (Phase 6)
- Rebuilding barangay broadcast / drill UI elsewhere
- Dropping incidents.tracking_pin column
- Full admin design-system rewrite
