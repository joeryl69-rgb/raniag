# RANIAG — Round 42 Changes

The public heading no longer shows a raw `&amp;`. The live map, GPS
camera, Accept button, case documents, reports, and public tracking
pins are cleaned up so each screen says what it does.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Updates and announcements
=====================================================================
The landing-page title was escaped twice, so visitors saw
"Updates &amp; Announcements". The heading and the staff menu now say
"Updates and announcements".

Files:
  resources/views/public/home.blade.php
  resources/views/admin/announcements/index.blade.php
  resources/views/components/sidebar-nav.blade.php

=====================================================================
2. Live map is easier to read
=====================================================================
The side panel is split into Risk, Places, and Route so a phone does
not scroll through every list at once. A zone popup shows type,
barangay, and the advisory. A center popup shows barangay, address,
and capacity. Barangay risk stays a count only.

Staff draw those shapes under Zones and centers. That page is the
editor. The public Live map is the view. Risk is not drawn there; it
is the number of open reports per barangay.

Files:
  resources/views/public/hazard/map.blade.php
  public/js/public-hazard-map.js
  public/css/public.css
  resources/views/admin/hazard/index.blade.php
  app/Services/SituationalMapService.php
  app/Http/Controllers/Public/HazardMapController.php
  routes/public.php
  tests/Feature/Public/HazardMapTest.php

=====================================================================
3. Accept no longer sticks on the loading screen
=====================================================================
Accept was waiting on the reporter email inside the database
transaction. A slow mail server left the loading overlay up. The
notice is sent after the browser already has the next page. The
overlay also clears itself after 20 seconds if a request never
returns, and a restored browser page cannot reload itself in a loop.

Files:
  app/Services/IncidentService.php
  resources/views/layouts/app.blade.php

=====================================================================
4. Voice dictation and document scanning are removed
=====================================================================
The public report form no longer has a Voice button. Case documents
are photo uploads only. The automatic text scanner is gone.

Files:
  resources/views/public/report/create.blade.php
  public/js/public-report.js
  resources/views/admin/incidents/show.blade.php
  app/Http/Requests/Admin/StoreIncidentDocumentRequest.php

=====================================================================
5. GPS camera shares one location fix
=====================================================================
The case map and the GPS camera were each asking the phone for
location. The second request often got nothing, so the map moved
while the camera stayed on "Waiting for GPS" until a tab switch.
They now share one fix. Reaching the scene no longer reloads the
page, which was closing the camera. The barangay name comes from
Pamplona's own boundaries, so the shutter is not waiting on a slow
outside address lookup.

The text box on the case is labeled as a message the reporter
receives, plus an optional staff note the reporter does not receive.

Files:
  public/js/gps-camera.js
  public/js/raniag-location-ping.js
  resources/views/components/gps-camera.blade.php
  resources/views/components/responder-field-strip.blade.php

=====================================================================
6. Reports and public tracking
=====================================================================
Generate Reports is two steps. Step 1 sets the period. Step 2 is three
separate downloads, each stating the question it answers: the incident
register, the working spreadsheet, and the operations brief.

The brief is no longer a generic status/type/hotspot/trend set. It
shows what is still open by priority, how long the first assignment
took, which barangay needs which kind of team, which office is holding
the open cases, and when reports arrived.

On the public tracking map, two agencies at the same spot are offset
so both names show. The public map asks for a fresh saved position
every few seconds, matching the movement the agency map already drew
from the phone.

The agency GPS camera no longer stamps every photo as Pamplona. A fix
inside a Pamplona barangay keeps that barangay name. A fix outside,
such as Langagan, uses the real place name, and the accuracy reading
from the phone is shown.

Files:
  resources/views/admin/reports/index.blade.php
  resources/views/admin/reports/chart_summary_pdf.blade.php
  app/Http/Controllers/Admin/ReportController.php
  public/js/gps-camera.js
  public/js/raniag-location-ping.js
  app/Http/Controllers/Public/HazardMapController.php
  public/js/public-track-map.js
  public/js/raniag-dispatch-map.js
  public/js/raniag-mapbox.js
  resources/views/public/track/show.blade.php
  app/Http/Controllers/Public/IncidentTrackController.php
  app/Http/Controllers/Shared/LiveUnitsController.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Home page heading reads "Updates and announcements", not "&amp;".
2. Live Map side panel has Risk, Places, and Route.
3. Accept an assigned case. The loading screen should clear.
4. Public report has no Voice button.
5. Case documents offer a photo upload and do not scan text.
6. On a case, open the GPS camera while En route. Coordinates should
   appear without switching browser tabs. Outside Pamplona the place
   line names the real barangay (for example Langagan), and the badge
   shows the meter accuracy from the phone.
7. Generate Reports shows three separate downloads. The operations
   brief lists open cases by priority, assignment time, places to send
   people, office load, and when reports arrived.
8. Public tracking shows two agency names when they share a location,
   and the pin moves after the responder is En route.
