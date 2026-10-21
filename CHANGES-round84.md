# Round 84 — Mobile dock and dialog sizing

## Changes

- Fixed false keyboard detection hiding the mobile dock during initial mobile viewport adjustments or pinch zoom. The dock is now hidden for a keyboard only when an editable field is focused and the visual viewport has actually shrunk relative to its baseline.
- Ensured the dock overrides the desktop-only hide rule on compact landscape touch screens without changing its normal portrait layout.
- Enabled safe-area viewport sizing for the staff PWA layout so fixed navigation and dialogs can respect device insets.
- Added horizontal gutters and a maximum width to scrollable dialogs on phones, including short landscape viewports, while preserving the scrollable form/body layout.
- Retained the compact landscape sidebar and incident filter layouts from the previous round.

## Verification

- Extended the incident interface regression test to check the dock's editable-focus keyboard detection and mobile/landscape rules.
- Focused feature tests passed: 11 tests, 133 assertions (`IncidentInterfaceTest`, `WorkspaceModalRegressionTest`, and `WorkspaceTableSortTest`).
- JavaScript syntax, CSS parsing, and `git diff --check` passed.
