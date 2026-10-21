# Round 82 — Mobile workspace fixes

## Changes

- Anchored the shared activity-log filter panel to the phone viewport when opened, with a bounded, scrollable height that adapts to the available space and safe-area insets.
- Removed the SMS message cell's desktop minimum width on phones and allowed long text and URLs to wrap within the card layout.
- Improved narrow-screen workspace headers so view selectors and related actions wrap instead of being pushed past the right edge; the Incident Types add action moves onto its own row on phones.
- Kept the Incident queue within the viewport while preserving horizontal scrolling for its wide data table, and made its filter panel phone-sized and independently scrollable.
- Made incident resolution edit dialogs scrollable and constrained scrollable dialogs to the available mobile viewport.
- Increased page-content clearance above the mobile dock so the last controls and rows can be reached without being covered.

## Verification

- Added assertions for the affected register headers to the existing workspace register feature tests.
- Focused feature tests passed: 11 tests, 128 assertions (`WorkspaceTableSortTest`, `WorkspaceModalRegressionTest`, and `IncidentInterfaceTest`).
- JavaScript syntax check, CSS parsing, and `git diff --check` passed.
