# RANIAG — Round 38 Changes

Resolve Incident no longer covers the case file. A second assigned
agency is checked directly against the assignments table. Tracking
opens on a normal page so a refresh does not expire. Desktop report
buttons sit under the step, and the live pin matches the agency row.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Resolve Incident stays in the form
=====================================================================
The resolution button had the same full-width fixed bar as Accept.
It pinned across the bottom of the screen and covered Case Details.
Accept and Resolve are ordinary buttons in the case column again.

Files:
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php
  public/css/public.css

=====================================================================
2. Second assigned agency
=====================================================================
Opening a case now reads the assignments table for the logged-in
agency, instead of the assignment list already loaded for the first
assignee. Personnel can still open a case assigned to them or to
their agency.

Files:
  app/Http/Controllers/Agency/IncidentController.php
  app/Http/Controllers/Personnel/IncidentController.php
  app/Policies/IncidentPolicy.php

=====================================================================
3. Tracking page expired (419)
=====================================================================
Looking up a report used to leave the browser on the form submission.
Refreshing that page resubmitted an old token and showed 419.
A successful lookup now opens /track/{tracking number}. Refresh is a
normal page load. If the session is gone, the lookup form asks for
the tracking number again instead of expiring.

Files:
  app/Http/Controllers/Public/IncidentTrackController.php
  routes/public.php
  tests/Feature/Public/IncidentReportTest.php

=====================================================================
4. Report wizard on desktop
=====================================================================
Back and Next are no longer a stretched bar over the form. On a wide
screen they sit under the current step, normal size, aligned to the
right. On a phone they stay in a bar at the bottom.

Files:
  public/css/public.css

=====================================================================
5. One centered responder mark
=====================================================================
The map pin and the responding-agency row use the same centered
marker. The name sits under the pin. The rotated teardrop and the
separate person glyph are gone.

Files:
  public/css/public.css
  public/js/raniag-dispatch-map.js
  public/js/public-track-map.js
  resources/views/public/track/show.blade.php
  public/sw.js

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Agency case file while en route: Resolve Incident is in the form,
   not a bar across the page.
2. Assign a second agency and open the case with that agency's login.
3. Track a report, then refresh. The page should stay open.
4. Report an incident on a desktop width: Back and Next sit under the
   step and do not cover the type cards.
5. Public tracking map and the agency row use the same centered mark.
