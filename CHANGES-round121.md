# Round 121 — Keep all public tracking activity in sync

- Fixed the previous history poller contract mismatch: its response did not contain the wrapper element the browser expected, so resolutions and timeline updates were never applied.
- Replaced separate page-refresh and history-refresh paths with one authenticated, no-cache tracking feed for case status/progress, all active agency or personnel assignments, public timeline entries, and every agency resolution.
- The feed refreshes every three seconds and updates all three page regions from the same fresh tracking lookup. The responder map continues using its own live-unit feed.
- Published the poller at a new asset URL to avoid browsers or the CDN reusing the older, long-cached script.
- Added regression coverage for post-load status changes, agency acceptance, response phases, assignment additions, and newly submitted resolutions.

## Verification

- `php artisan test tests/Feature/Public/LiveUnitsAndRiskTest.php tests/Feature/InvestigationUpdateTest.php`
- `node --check public/js/public-track-updates.js`
