# Round 94 — Reports studio and consistent portrait mastheads

## Changed

- Reworked the report selection page heading and added selection-aware KPI cards for incident reports and evacuee records.
- Clarified the two spreadsheet downloads as a sortable table-only workbook and a workbook with a chart summary sheet. Print preview now has its own presentation area.
- Removed the unused paper-size and layout selectors. PDF, Word, Excel, and print output now use A4 portrait by default, including when unsupported paper or landscape query values are supplied.
- Removed the incident-description Summary column from generated tables and exports; incident narrative and decision-report summaries remain unchanged.
- Standardized the institutional masthead across report PDFs, with compact seals and a legible four-line heading.
- Preserved report filters and ensured export files contain all matching records, regardless of the current table page.

## Verification

- `php artisan test tests/Feature/Admin/IncidentPresentationTest.php` — **6 tests, 101 assertions passed**.
- `php artisan test tests/Feature/Admin/ReportRecordTest.php tests/Feature/Admin/ReportArchiveTest.php tests/Feature/Admin/ReportPictureTest.php` — **45 tests, 281 assertions passed**.
- PHP syntax checks for the changed PHP classes/tests and `git diff --check` passed.
