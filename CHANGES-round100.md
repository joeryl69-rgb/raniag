# Round 100 — Unified report masthead and cleaner presentation exports

## Changed

- Added a shared report-letterhead specification for seal sizes, three-column alignment, institutional text, colors, spacing, and page margins.
- Applied the shared masthead to PDF report templates and print preview, including the single-incident PDF used by admin document requests.
- Updated generated Word documents to use the same masthead content, seal dimensions, column proportions, spacing, rule color, and A4 portrait margins.
- Removed the redundant date-range, selection-summary, and generated-at block from presentation PDF, Word, and print output.
- Added consistent horizontal margins and spacing around the report content while keeping tables within the A4 portrait printable width.

## Verification

- Focused report, archive, document, and presentation tests: **56 passed, 486 assertions**.
- PHP/Blade syntax checks and `git diff --check` passed.
- Visual rendering was not verified in Microsoft Word or browser print UI; those renderers can vary.

## Round 100 follow-up — gathered-report controls and export typography

- Replaced the gathered-report toolbar’s barangay native popup with a progressively enhanced searchable list that opens below its field, scrolls within a bounded height, supports keyboard selection, and keeps the named native select as the submitted/filter value and no-JavaScript fallback.
- Standardized report PDF, Word, and print body typography on the masthead’s DejaVu Sans baseline at 10 pt, including older PDF template declarations that had specified Helvetica or smaller body sizes.
- Added report-view and export assertions for the searchable filter control and matching PDF/Word/print font declarations.
- Browser, mobile-device, Microsoft Word, and print-dialog visual rendering were not available for this change; behavior is covered by focused feature tests and static/syntax checks.

## Verification

- `php artisan test tests/Feature/Admin/IncidentPresentationTest.php tests/Feature/Admin/PrintableDocumentRequestFeedbackTest.php` — **10 tests, 190 assertions passed**.
- `node --check public/js/report-searchable-select.js`, PHP syntax checks, and `git diff --check` passed.
