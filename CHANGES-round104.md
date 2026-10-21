# Round 104 — Non-searchable custom dropdowns

## Changed

- Restored the custom dropdown menu appearance across staff and public single-select controls, including rounded trigger buttons, selected-option highlighting, and a bounded scrollable option panel.
- Removed all dropdown search inputs and filtering behavior. Options are selected directly, with keyboard navigation retained.
- Kept each native select as the canonical submitted field and synchronized it with the custom menu, preserving existing change handlers, validation, and filter application.
- Kept the separate custom date calendar unchanged.

## Verification

- Focused report, filter workflow, and incident-interface tests passed.
- JavaScript syntax, Blade syntax, and `git diff --check` passed.
