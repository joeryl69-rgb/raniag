# Round 123 — Make incident queues role-appropriate

- Changed the administrator incident queue default to submitted reports, ordered newest reported first. Added an explicit All reports filter and connected the All incidents KPI to it.
- Removed the synthetic Active cases filter from the shared queue controls. Agency and personnel queues now default to all currently assigned cases.
- Restricted agency and personnel queue results and their status choices to incidents with active assignment records, so completed or withdrawn historical assignments do not keep cases in the queue.
- Added a stable incident-ID tie-breaker to queue ordering and regression coverage for newest-first admin results and active-assignment visibility.
- Re-ran the public tracking live-update and final-agency-resolution regression tests to verify the existing tracking fix remains intact.

## Verification

- `php artisan test --compact tests/Feature/Admin/UnusedFilterOptionTest.php tests/Feature/AssignmentServiceTest.php tests/Feature/Public/LiveUnitsAndRiskTest.php`
- `git diff --check`
- PHP syntax checks for the changed controllers, repository, and tests.
