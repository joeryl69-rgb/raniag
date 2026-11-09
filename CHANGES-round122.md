# Round 122 — Ensure the final agency resolution appears live

- Removed incident-creation-time filters from status timeline and public resolution loading. Clock or database timestamp differences could make newly recorded final status events and resolutions look older than the incident and disappear from the live feed.
- The tracking poller now aborts stalled requests after ten seconds, retries automatically, and refreshes immediately when the page regains focus or network connectivity.
- Bumped the tracking script cache version so browsers load the corrected polling behavior.
- Added an end-to-end regression test that submits a partial resolution from the first agency, then submits the final resolution from the last agency and confirms the live feed shows both resolutions and the resolved case state.

## Verification

- `php artisan test tests/Feature/Public/LiveUnitsAndRiskTest.php tests/Feature/InvestigationUpdateTest.php`
- `node --check public/js/public-track-updates.js`
