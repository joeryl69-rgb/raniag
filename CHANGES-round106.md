# Round 106 — Agency document-ready routing and offline public reporting

## Changed

- Sent an agency “document ready” notice (including when the PDF is already generated) to My document requests with that request highlighted, instead of opening the PDF. View Printable PDF on that row still opens the file. Admin document-request notices still open the admin list with the same highlight behavior.
- Rewrote the public offline fallback so it tells people they can still file a new report, and added a Report an incident action next to Try Reconnecting.
- Precached the public report page and its scripts in the service worker so a first offline visit can open the form after the phone has saved it once while online, preserving the cached pages while the worker updates.
- Added server-side idempotency for queued report delivery so repeated retries create one incident, and clear feedback if offline storage is unavailable.
- Restyled Need help? Ask JO as a filled pill with an icon, instead of a plain text link.
- Added an offline-only note on the report form that a report can still be filed if the signal drops, with or without a GPS photo.
- Split the Saved on this phone popup: with a GPS photo it says the photo and location stay on the phone and send when the connection returns; without a GPS photo it says no map location was saved and MDRRMO will see that the location was unavailable.

## Verification

- Document-request notification test now expects the agency desk URL and loads that highlighted row, including View Printable PDF.
- Offline reporting tests cover the offline page copy, hidden-by-default report-form note, repeated retry deduplication, Ask JO markup, and the two popup messages in the outbox script.
- JavaScript syntax checks for `report-outbox.js` and `sw.js` passed.
- Browser checks: Ask JO appears as a designed pill after dismissing the coach; the report form stays reachable from the offline card after the page is cached; the save popup copy differs for GPS-photo vs no-photo submissions.
- Deployed to Hostinger as `db0e9b4`.
