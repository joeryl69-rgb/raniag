# Round 79 — Workspace navigation and interactions

## Updated

- Applied the animated, accessible workspace-tab treatment to Hazard Map and the Agencies & Personnel workspace. The agency and personnel registers now appear as separate tabs, with matching sidebar entries and tab state preserved in the URL, filters, sorting, and pagination.
- Changed Notifications All/Unread switching to fetch and transition the server-rendered results in place. Server filtering, browser history, counts, and the active Delete All scope stay synchronized.
- Reworked the Feedback & Concerns response disclosure with an interruptible expand/collapse animation. Live refresh pauses while a response panel is open to avoid moving controls during review.
- Improved hazard-area creation with draw, adjust, clear, and save guidance; disabled saving until a boundary exists; restored old drafts with visible recovery feedback; and added server validation for GeoJSON Polygon rings, closure, coordinate ranges, and usable area.
- Added accessible hover/focus tooltips to the collapsed desktop sidebar without replacing existing navigation behaviors.
- Reworked the mobile bottom dock into evenly sized touch targets on narrow screens, retained safe-area spacing, and hid it while the on-screen keyboard is open. Hazard Map now stacks its map and controls on tablet and mobile widths.
- Kept Updates & Announcements in its existing editorial card layout. A table/card toggle would not improve this publication workflow.

## Verification

- Focused feature tests: **9 passed, 88 assertions** (hazard drawing, workspace sorting/tabs, evacuee registry, notifications, and feedback filters).
- PHP syntax checks passed for the changed controller and test files.
- JavaScript syntax checks passed for the updated notification, feedback disclosure, and live-refresh scripts.
- The shared workspace stylesheet parsed successfully.
- Blade templates compiled successfully; compiled views were cleared afterward.
- `git diff --check` passed.
