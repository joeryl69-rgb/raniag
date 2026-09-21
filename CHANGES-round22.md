RANIAG changelog — round 22 (Hazard map 500 fix + QR poster CRUD)

Date: 2026-09-21

Hotfix after round 21 deploy: `/admin/hazard` returned HTTP 500, blocking
zone drawing. QR posters were generate-only (select barangays) with no
saved records or edit/delete. This round fixes the crash, wires admin
zones to the public map UX, and adds full QR poster CRUD.

Commit on `main`:
  67b09ca  Fix admin hazard 500 and add QR poster CRUD.

HOW TO APPLY
Push/pull `main` on Hostinger (GitHub Actions deploy runs
`git reset --hard origin/main`, `composer install`, `npm run build`,
`php artisan migrate --force`, optimize caches).

After deploy, confirm migrations applied:
  php artisan migrate --force
  → 2026_09_21_180000_add_color_to_hazard_zones_table  (round 21)
  → 2026_09_21_190000_create_qr_posters_table           (this round)

=====================================================================
Root cause — admin hazard 500
=====================================================================
Blade misparsed arrow-function arrays inside `@json(...)`:

  @json($zones->map(fn ($z) => [ ... ]))

Compiled view threw: Unclosed '[' does not match ')' → HTTP 500.

Fix: prepare `mapZonesJson` / `mapCentersJson` in HazardEvacController
and pass plain arrays into the view. `@json($mapZonesJson)` is safe.

=====================================================================
1. Hazard zone mapping (admin + public)
=====================================================================
- HazardEvacController::index
    Builds map JSON in PHP; Schema::hasTable guards if tables missing
    Passes publicHazardMapUrl for “Open public Hazard Map” button
- storeZone: only writes `color` when column exists (safe if migration lag)
- destroyZone / destroyCenter: DELETE routes + UI buttons
- admin/hazard/index.blade.php
    Uses mapZonesJson / mapCentersJson (no Blade arrow @json)
    Link to public `/hazard-map`
    Delete zone / delete center actions
- Public `/hazard-map` already shows active zones + open centers;
  admin draw → save → appears on public map when is_active

Routes added:
  DELETE admin/hazard/zones/{zone}     → admin.hazard.zones.destroy
  DELETE admin/hazard/centers/{center} → admin.hazard.centers.destroy

=====================================================================
2. QR report posters — full CRUD
=====================================================================
Problem: Round 21 only generated ephemeral posters from GET checkboxes.
No create/edit/delete of saved posters.

- Migration: qr_posters (title, barangay, notes, is_active, timestamps)
- Model: App\Models\QrPoster (reportUrl(), qrImageUrl())
- QrPosterController: index, store, update, destroy
- admin/qr_posters/index
    Create / Edit form (title, barangay, notes, active)
    Saved list with Edit + Delete
    Print grid of active posters (api.qrserver.com QR images)

Routes:
  GET    admin/qr-posters              → index
  POST   admin/qr-posters              → store
  PUT    admin/qr-posters/{qrPoster}   → update
  DELETE admin/qr-posters/{qrPoster}   → destroy

Scanning a poster still opens public `/report?barangay=…` (unchanged).

=====================================================================
3. Docs
=====================================================================
- CHANGES-round21.md committed on the same push (round 21 write-up)

=====================================================================
Verify after deploy
=====================================================================
1. /admin/hazard loads (no 500)
2. Draw polygon → Save zone → “Open public Hazard Map” shows the zone
3. Delete a zone / center from the lists
4. /admin/qr-posters: Create poster → Edit → Delete → Print active
5. Scan QR → public report form with barangay prefilled

=====================================================================
Out of scope
=====================================================================
- Mapbox / native mobile mapping (Phase 6)
- Local QR library (still third-party image API)
- Editing existing zone geometry after save (delete + redraw)
