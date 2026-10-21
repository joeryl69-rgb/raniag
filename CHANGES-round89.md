# Round 89 — Live map center name and shared table sorts

## Fixed

- The nearest open center name on the public live map stays light in light mode. The name is a button on the dark map panel, and light mode was using the browser's default black text. Dark mode already rendered it light.

## Changed

- Column sorting now follows the same rule on every account. Each register keeps the column that changes how the list is read. Search and filters cover the rest.
- Administrator: incident types sort by Name, the agency directory by Agency, the personnel directory by Name, personnel roles by Role title, QR posters by Title, SMS alerts by Sent / attempt, and audit trails by Occurred at. Case documents no longer sort by column; tracking search and the status filter remain.
- Agency: resolved reports sort by Reported at, and document requests sort by Requested at.
- Agency and personnel dispatches keep Priority and Reported time inside Filters.

## Verification

- Incident queue, live map, and workspace sort tests passed: **13 tests, 197 assertions**.
- On the public live map in light mode, the nearest-center name computed to the same light color used in dark mode (`#f0f5f6`).
