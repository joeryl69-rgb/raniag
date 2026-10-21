# Round 95 — Reporting page and document redesign

## Changed

- Reworked the report-generation page with a clearer heading and selection-aware KPI cards for report count, open/in-progress incidents, high/critical priority incidents, and represented areas.
- Removed the Paper and Layout controls. Generated report documents now use A4 portrait by default.
- Replaced the crowded export-button row with distinct Excel table and Excel + charts download cards, plus separate Word and PDF actions.
- Moved printing into its own presentation area with an in-page print preview.
- Removed the incident Summary column from generated report tables and exports.
- Updated the shared report masthead across PDF report types: reduced seal sizes and increased the visibility of institutional headings.
- Preserved report filters and ensured exports contain every matching record, including records on later table pages.

## Verification

- `php artisan test tests/Feature/Admin/IncidentPresentationTest.php` — **6 tests, 101 assertions passed**.
- `php artisan test tests/Feature/Admin/IncidentPresentationTest.php tests/Feature/Admin/ReportRecordTest.php tests/Feature/Admin/ReportArchiveTest.php tests/Feature/Admin/ReportPictureTest.php` — **51 tests, 382 assertions passed**.
- PHP syntax checks and `git diff --check` passed.
- Browser-based visual review was not performed.
