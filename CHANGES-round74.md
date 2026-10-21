# Round 74 — Purpose-built staff review and triage

- Refined audit and SMS registers around event/actor/time and recipient/message/provider/status, and corrected the shared compact-filter popover stacking and clipping so controls remain opaque and usable above register content.
- Reworked Document Requests and Case Documents into case-file review layouts with deliberate empty states and better filter placement.
- Restructured Feedback & Concerns into a triage-oriented case view, separating source/category, message, contact, status actions, private notes, and reply controls while retaining escaped content and existing routes/forms.
- Improved agency/personnel directory and account form presentation, including a more restrained workspace canvas, without changing validation or role switching.
- Removed always-visible QR imagery from poster rows and added an on-demand accessible preview while retaining poster creation/editing, active controls, downloads, and print-sheet source markup.
- Refined Incident Types, Hazard Map, and evacuee registry task sections, and tightened Admin/agency/personnel incident-detail density without changing queue design, role scopes, maps, or response actions.
- Added scroll locking, focus containment, and scroll/focus restoration to the public Support confirmation dialog.
- Validation: Blade cache compilation and clearing succeeded; six focused Laravel feature files passed (17 tests, 153 assertions); support-dialog JavaScript passed `node --check`; both changed CSS files parsed with PostCSS; `git diff --check` passed.
- Browser verification confirmed the public Support dialog locks background scrolling and restores the page when closed. Staff/admin page visual review was unavailable because no authenticated staff session was available; no user screenshot attachment directory was present.
