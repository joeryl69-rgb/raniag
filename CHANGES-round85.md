# Round 85 — Persistent incident navigation dock

## Changes

- Kept the mobile dock visible and interactive while scrolling the incident list instead of allowing the scroll-direction behavior to hide it on this page.
- Gave the incident-page dock an explicit viewport-bounded width and five-column layout across phone and tablet widths, including compact landscape.
- Left the dock's existing scroll-responsive behavior unchanged on other pages.

## Verification

- Extended the incident queue feature regression test to assert its persistent dock variant.
- Incident queue feature tests passed: 4 tests, 62 assertions.
- JavaScript syntax, CSS parsing, and `git diff --check` passed.
