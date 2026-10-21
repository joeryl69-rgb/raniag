# Round 101 — Consistent incident-report masthead and filter dropdowns

## Changed

- Removed “RANIAG Operations Center” from the shared report letterhead office line.
- Standardized incident-report titles to “RANIAG Incident Report” in gathered-report PDF, Word, and print output, and in printable incident documents used by document requests. Descriptive titles for other report types remain specific to their content.
- Standardized report PDF, Word, print, and spreadsheet typography on Arial to avoid Word substituting an unavailable font with Times New Roman.
- The searchable-dropdown experiment described below was later removed in Round 103 after the interface was found less usable than native dropdowns.

## Verification

- Focused report, document-request, and filter workflow tests at the time: **22 passed**.
- JavaScript syntax, PHP/Blade syntax, and `git diff --check` passed.
- Microsoft Word, Excel, and browser/mobile visual rendering were not available for direct visual confirmation.
