# Round 81 — Animated report and map tabs

## Updated

- Updated the Reports **Report home / Saved reports** navigation to reuse the shared animated workspace tab styling, including an animated active state, visible report count, and `aria-current` indication.
- Reviewed the other tab-like views: the admin Hazard Map already uses the shared workspace tabs; the public Hazard Map uses a separate compact segmented control, so added matching selection transitions and a pane entrance animation there, with reduced-motion support.
- Confirmed the remaining searched workspace views use filters or register controls rather than page tabs; no unrelated controls were restyled.

## Verification

- Reports archive test: **1 passed, 22 assertions**.
- Public Hazard Map test updated to assert tab markup.
- Both updated stylesheets parsed successfully; Blade compilation and `git diff --check` passed.
