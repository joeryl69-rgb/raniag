# Round 80 — Separate coordination directories

## Updated

- Replaced the combined Agency/Personnel tab panel with separate directory pages at `?directory=agencies` and `?directory=personnel`. Only the selected register is rendered, avoiding hidden-tab content and making the Personnel table reliably visible.
- Simplified Coordination navigation: Agency directory is a direct link, while Personnel expands to Personnel directory and Personnel Roles. The Personnel directory also links directly to role management.
- Updated the directory title, summary, KPI, and primary action to match the selected directory.
- Verified the Reports workspace keeps its Report home and Saved reports tabs and that the archive tab is active on the Saved reports view.

## Verification

- Focused workspace and reports archive tests: **5 passed, 60 assertions**.
- Updated Blade templates compiled successfully.
- PHP syntax and `git diff --check` passed.
