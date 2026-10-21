# Round 86 — Incident viewport overflow containment

## Root-cause approach

- Scoped the fix to the administrator Incident queue page so other pages retain their existing viewport and dock behavior.
- Contained the incident page's intentionally wide, internally scrollable table at the wrapper and content boundaries, preventing table width from expanding or horizontally panning the page viewport.
- Anchored the incident dock to the current `visualViewport` center and bottom inset, and recalculate its placement on viewport resize and pan. This avoids relying on a percentage position that can drift when the browser's visible viewport and layout viewport diverge.

## Verification

- Extended the incident interface test to assert the route-specific page marker and visual-viewport dock positioning.
- Administrator incident queue feature tests passed: 4 tests, 65 assertions.
- JavaScript syntax, CSS parsing, and `git diff --check` passed.
