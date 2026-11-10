# Round 124 — Keep public tracking active across staff session changes

- Added a short-lived, signed, HttpOnly cookie scoped to each tracking page after a successful tracking-number lookup.
- Public tracking refreshes, the live-update poll, and evidence requests can use that cookie if an unrelated staff login/logout replaces the shared Laravel session.
- The live poll refreshes the cookie only when it is nearing expiry; polling cadence and resolution submission behavior are unchanged.
- Added regression coverage for losing the Laravel session after lookup, then reopening the tracking page and receiving the final resolution from the live-update endpoint.

## Verification

- `php artisan test --compact tests/Feature/Public/LiveUnitsAndRiskTest.php`
- `git diff --check`
- PHP syntax checks for the updated controllers, access service, and tests.
