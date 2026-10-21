# Round 102 — Consistent dropdowns and calendar controls

## Changed

- Replaced native single-choice select popups across staff and public layouts with the shared searchable option list used by the barangay filter. Original select fields remain the submitted values; change events and dynamically inserted selects remain supported.
- Removed the focus glow from the custom dropdown input while retaining a clear border and keyboard focus indicators for menu options.
- Replaced native date picker popups for From/To and other date fields with a shared calendar panel matching the dropdown treatment. It supports month navigation, locale-formatted typing, min/max dates, required validation, Today/Clear actions, keyboard dismissal, and mobile-friendly bounded scrolling.
- Kept original date inputs as the named canonical values, so existing GET filters, form submission, and active-filter chips continue to read ISO dates.
- Audited remaining specialized browser controls: file, color, range, and numeric inputs already use the project’s Bootstrap control styles; multi-select behavior remains native by design.

## Verification

- Focused report, filter workflow, and incident-interface tests at the time: **32 passed**.
- JavaScript syntax, Blade syntax, and `git diff --check` passed.
- Browser visual verification was not available for authenticated report pages.

## Round 103 follow-up

- Removed the searchable dropdown enhancement in favor of native select controls. See [CHANGES-round103.md](./CHANGES-round103.md).
