# Round 78 — Register view polish and contextual controls

- Reorganized the evacuee registry: the count and view selection have a clear summary row, origin filters and search are grouped as dedicated controls, and those controls plus Table/Cards are hidden when the register is empty. Removed the redundant visible “Table view selected” status text; the toggle's pressed state remains accessible.
- Polished card-mode table rows so values align consistently, action buttons stay in a horizontal wrapping action group rather than stacking, empty states remain centered, and incident-type icons stay centered within their circular badges.
- Refined announcement placement by removing the extra enclosing panel and arranging the announcement cards in a more balanced responsive grid.
- Removed table/card switching from compact or specialized lists where it added little value (dashboard dispatches, personnel role catalog, QR poster management, and saved report files). Kept it on larger searchable operational registers and incident/type queues.
- Added regression checks for the empty and populated evacuee register controls.
- Validation: evacuee/workspace regression tests passed (4 tests, 53 assertions); `table-view-mode.js` passed `node --check`; `workspace.css` parsed with PostCSS; and `git diff --check` passed. Browser fixtures confirmed left-aligned card values, horizontally arranged action buttons, centered incident icons in their circular badges, and a balanced three-column announcement layout at desktop width.
