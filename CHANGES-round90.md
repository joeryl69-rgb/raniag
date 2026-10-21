# Round 90 — One report QR, case actions, and public tracking

## Removed

- Barangay QR posters and the later admin Report QR page are both gone. The sidebar no longer has that item. Old poster rows stay in the database and are unused.

## Added

- The public homepage shows one QR for the general report form. It uses the old navy modules and center seal, drawn from the address open in the browser. Download QR image saves that code only, as `raniag-report-qr.png`. It does not choose a barangay. The GPS camera on the form still sets the location.

## Fixed

- A responder’s last GPS was drawn on every case assigned to that agency, including cases that were not marked En route. The pin and route now appear only when that case is En route or On scene. While a case is in one of those phases, a new GPS fix moves the pin on the agency case, the personnel case, the administrator case, and the public tracking page.
- The public live response map used a plain red dot for the incident. It now uses the incident type’s icon and color. Admin, agency, and personnel case maps pass the same icon and priority.
- An empty Description card is left off the public tracking page. Evidence stays the size of the photo.
- Public Full History names who recorded each step and states what happened.

## Changed

- Accept, Mark En route, and Post Update on agency and personnel cases fit their labels. The accept line says location sharing starts at En route.
- The investigation note box says the note is for MDRRMO and the responding team, and is not shown on the public tracking page. New notes follow that. A note already saved as public, such as “testing 123” on RAN-21JBQN, stays on that page and now includes who wrote it.

## Verification

- Homepage, live-unit, tracking, report-QR, and workspace tests passed: **16 tests, 164 assertions**. The agency case page was checked again for the new note wording.
- The admin Report QR page is removed. The homepage code is the old navy QR with the center seal, and Download QR image saves that 640×640 code only.
- A second GPS fix on an en-route agency case and an on-scene personnel case updates the admin, agency, personnel, and public live-unit feeds.
- On public tracking for RAN-21JBQN, the scene pin was the blue Flood water icon, the empty Description card was absent, and Full History named the recorder and the action.
- Staff sign-in from an automated browser stops at the email code, so the case-action buttons still need a signed-in look.
