RANIAG changelog — round 28 (hazard My location + Mapbox route,
JO report stepper, calm loading, Hostinger deploy fix)

Date: 2026-09-23 / 2026-09-24

Builds on Round 27’s live hazard map and JO guide. This round makes
**My location** opt-in with live GPS + Mapbox directions, merges JO into
a CoreUI-inspired report stepper, softens public loading, and repairs
GitHub Actions → Hostinger auto-deploy (Composer `proc_open` + asset
upload).

Commits on `main` (this round):
  6363fa7  Round 28: live YOU map focus, JO report stepper, calm loading
  0da3301  Rename YOU → My location on the hazard map
  17b765c  Mapbox tiles + walk/drive route to nearest evacuation center
  9ae4389  My location toggle clears/refreshes Mapbox route dynamically
  b688179…367c7ba  Hostinger deploy hardening (SSH, npm on runner,
                   --no-scripts, SCP public/build)

HOW TO APPLY
Push `main` (or wait for GHA after merge). Deploy workflow now:
  1. npm ci + npm run build on the runner
  2. SSH: git reset --hard origin/main, composer --no-scripts, artisan
  3. SCP public/build → Hostinger (build is gitignored)

Hostinger `.env` (do not commit secrets) — if missing:
  MAPBOX_ACCESS_TOKEN=pk.…
  MAPBOX_STYLE=mapbox/streets-v12
  MAPBOX_DIRECTIONS_PROFILE=walking
Then on the server (or rely on GHA):
  php artisan config:cache
  php artisan optimize:clear
Hard-refresh /hazard-map and /report. Optional: clear Hostinger website
cache.

Live site:
  https://mediumorchid-weasel-407759.hostingersite.com

=====================================================================
1. Hazard map — My location enable / disable
=====================================================================
Location tracking starts **off**. Users turn it on when they want live
GPS and a route; turning it off stops the watch and clears markers.

UI:
- Layer chip `#layer-you` labeled **My location** (unchecked by default)
- Header button `#hazard-locate-you` (“My location”) turns tracking on
  or re-centers if already on

Behavior (`setLocationEnabled`):
- On → `watchPosition`, blue YOU marker, nearest-center fetch, Mapbox
  route updates as you move (throttled)
- Off → `clearWatch`, remove YOU layer, clear route / nearest / status
- JO tip text updates for on / off / denied

Files:
  resources/views/public/hazard/map.blade.php
  public/js/public-hazard-map.js
  public/css/public.css
  public/js/public-guide.js

=====================================================================
2. Mapbox basemap + walk / drive route
=====================================================================
When `MAPBOX_ACCESS_TOKEN` is set:
- Leaflet basemap uses Mapbox Streets (style from `MAPBOX_STYLE`)
- Directions API draws a polyline to the nearest **open** evacuation
  center
- Walk / Drive radios switch profile; ETA / distance in `#route-box`
- Without a token, OSM tiles remain; route box stays hidden or idle

Config:
  config/raniag.php → map.mapbox_token / mapbox_style / directions_profile
  .env.example → MAPBOX_* keys
  Payload on hazard map blade → `mapbox_token`, `mapbox_style`,
  `directions_profile`

Files:
  config/raniag.php
  .env.example
  resources/views/public/hazard/map.blade.php
  public/js/public-hazard-map.js
  public/css/public.css

=====================================================================
3. JO merged into CoreUI-inspired report stepper
=====================================================================
Report wizard steps use a RANIAG-styled stepper (`rg-stepper-*`) with
JO’s coach avatar/copy beside the active step instead of a separate
floating coach-only layout. Success page still uses JO’s resolved pose.

Guide JS accepts both `.jo-report-coach-*` and `.rg-stepper-jo-*`
selectors so tips keep working.

Files:
  resources/views/public/report/create.blade.php
  resources/views/public/report/success.blade.php
  public/js/public-report.js
  public/js/public-guide.js
  public/css/public.css

=====================================================================
4. Calm public loading
=====================================================================
Global loading overlay and map overlays use a slim indeterminate
progress bar (no heavy spinner jank). Respects reduced-motion where
styles already do.

Files:
  resources/views/layouts/public.blade.php
  resources/views/public/report/create.blade.php
  public/css/public.css

=====================================================================
5. Hostinger GitHub Actions deploy — fixed
=====================================================================
Failures after Round 27 were not “wrong SSH password” alone:

1. Invalid appleboy inputs (`max_attempts` / `backoff`) → removed
2. Frontend built on runner but never uploaded (`public/build` gitignored)
   → `appleboy/scp-action` after SSH
3. `composer install` ran `package:discover`, which needs `proc_open`;
   Hostinger CLI disables it → `composer install … --no-scripts`

Workflow (`.github/workflows/deploy.yml`):
  checkout → npm build → SSH (git + composer --no-scripts + artisan)
  → SCP public/build → mark deployment

Required secrets (unchanged names):
  HOSTINGER_SSH_HOST, HOSTINGER_SSH_PORT, HOSTINGER_SSH_USER,
  HOSTINGER_SSH_PASSWORD

Files:
  .github/workflows/deploy.yml

=====================================================================
Verify after deploy
=====================================================================
1. /hazard-map → My location chip off by default; turn on → blue marker
   + route; turn off → marker and route clear.
2. Walk / Drive switch refreshes the polyline (with Mapbox token set).
3. Basemap is Mapbox Streets when token is present (not plain OSM).
4. /report → stepper with JO tip per step; submit still shows calm
   loading overlay.
5. Push a trivial commit to `main` → Actions “Deploy to Hostinger”
   succeeds (composer + SCP); site HEAD matches GitHub.
6. Hard-refresh if CSS/JS look stale; clear Hostinger cache if needed.
