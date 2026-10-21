# Round 76 — Workspace modal recovery and flexible registers

- Added a shared modal-root manager to the staff and public layouts. Existing and dynamically inserted Bootstrap modals are moved to `document.body`, avoiding clipped dialogs and stacking contexts inside workspace wrappers.
- Made the long Incident Types and announcement dialogs centered and internally scrollable, and updated the Incident Types surface styling for its new document-root placement.
- Added accessible Table / Cards controls to tabular registers and queue views across admin, agency, and personnel experiences, including account-specific saved preferences, generated field labels in card mode, responsive layouts, and labels refreshed after live updates. Existing table presentation remains the no-JavaScript default.
- Changed Feedback & Concerns so status, private-note, and reply controls start collapsed behind “Manage response”; refined the status edge treatment to avoid the previous heavy colored outline.
- Redesigned Updates & Announcements as responsive, theme-aware publication cards with clearer status, metadata, and action hierarchy.
- Added regression assertions for the shared modal manager, scrollable dialogs, register view controls, and collapsed feedback actions.
- Validation: modal regression tests passed (3 tests, 29 assertions); Feedback filter/triage tests passed (1 test, 10 assertions); both new JavaScript files passed `node --check`; `workspace.css` parsed with PostCSS; `git diff --check` passed.
- Browser fixtures confirmed that a dialog moves to `body`, opens with its backdrop and scroll lock, and cleans up on close; the table/card script labels cells and switches the view to responsive card layout. Authenticated staff-page visual verification was unavailable; staff rendering and modal markup were verified through Laravel feature tests.
