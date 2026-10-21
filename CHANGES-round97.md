# Round 97 — Reports filter refinement

## Changed

- Reordered the redesigned Reports filters so record set, barangay, incident type, and dates appear before the search field, which expands into the available space at the right.
- Kept Apply/Clear as explicit actions, retained active-filter chips and existing query parameters, and made the filter row wrap cleanly on narrow screens.
- Removed the visible Inside/Outside/All scope selector from incident-row report options while retaining its existing value in the form so saved report scope and data remain unchanged.

## Verification

- Focused Reports feature tests and `git diff --check` passed.
