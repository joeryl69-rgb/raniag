# Round 83 — Landscape and overflow corrections

## Changes

- Made filter popovers use a bounded viewport-sized panel on compact screens and short landscape viewports, including the incident queue filters.
- Reworked the incident queue controls into a compact search row and filter/action row on small screens; kept the incident table's own horizontal scrolling intact.
- Kept the mobile dock visible on short landscape touch screens, even when a handset's landscape width crosses the desktop navigation breakpoint, and reserved less vertical space for it in landscape.
- Kept the collapsible off-canvas sidebar and full-width content layout on those short landscape touch screens instead of leaving the desktop sidebar's narrow content column in place.
- Constrained form-wrapped scrollable modals so the dialog fills the available mobile height and its content area can actually scroll, including the Incident Type edit form.
- Overrode the SMS log's inherited desktop message minimum width specifically in card mode so long dispatch text and tracking URLs wrap within the card.

## Verification

- Extended the incident queue feature test to check the filter disclosure state and mobile dock markup.
- Focused feature tests passed: 11 tests, 131 assertions (`IncidentInterfaceTest`, `WorkspaceModalRegressionTest`, and `WorkspaceTableSortTest`).
- JavaScript syntax, CSS parsing, and `git diff --check` passed.
