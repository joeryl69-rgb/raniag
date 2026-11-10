# Round 125 — Refresh public live map data promptly

- Reduced public hazard-map snapshot polling from 60 seconds to 10 seconds, with an immediate refresh when the tab regains focus or network connectivity.
- Disabled browser caching for snapshot requests and marked snapshot responses `no-store`, so new reports, hazard zones, and open evacuation centers are fetched as fresh data.
- Added request timeouts, retry-on-next-poll behavior, and visible console warnings when a snapshot request fails.
- Map layer visibility remains controlled by the existing local checkboxes; snapshot refreshes redraw the layers without changing their selected visibility.

## Verification

- `php artisan test --compact tests/Feature/Public/HazardMapTest.php`
- `node --check public/js/public-hazard-map.js`
- PHP syntax check and `git diff --check`.
