# RANIAG — Round 44 Changes

A report submitted without a GPS photo or recording now gets one
clear correction, instead of the same location warning twice. The
live map draws a path to the nearest open center and follows the
user after My location is turned on.

Hostinger deploys when this lands on `main`.

=====================================================================
1. One correction when evidence has no GPS capture
=====================================================================
"Please correct the following" was repeating "Please share your
current location or capture a GPS photo." once for latitude and
once for longitude. The same contact sentence could also appear
twice when both phone and email were missing.

When the submission has no GPS photo or recording and no shared
location, the list now has a single line: no GPS photo or recording
was attached, and the current location was not shared. A duplicate
phone-or-email warning is collapsed to one line as well.

A GPS camera capture that already has coordinates fills a blank
location, so that photo or recording is not rejected as if the
reporter never shared a place. The form then opens the step that
actually failed.

A report with no photo still enters the verification queue when the
reporter leaves a name and a phone number.

Files:
  app/Http/Requests/Public/StoreIncidentReportRequest.php
  public/js/public-report.js
  resources/views/public/report/create.blade.php
  tests/Feature/Public/IncidentReportTest.php

=====================================================================
2. Live map route follows the user
=====================================================================
The route panel stayed on "Getting route…" and never drew a path.
The smooth-scroll script loads after the map and replaces the
Leaflet object, so later route drawing had nothing to draw with.
Each new GPS update started another request and threw the previous
result away, which left the label spinning.

The map keeps the Leaflet copy it loaded with. Turning on My
location shows the user, a short trail of movement, and the walk
or drive path to the nearest open center. The path refreshes when
the user moves, and the map stays with them. If the road route
cannot be loaded, a straight line and an estimate are shown instead
of another endless "Getting route…".

Files:
  public/js/public-hazard-map.js
  public/js/raniag-mapbox.js

=====================================================================
3. Situational map refresh reloads the feed
=====================================================================
The refresh button on the Situational Map called the same short-lived
cached feed and gave no sign that a click had done anything. It is
the same control on the administrator, agency, and personnel
dashboards.

A click now asks for a fresh feed, skips the administrator cache, and
redraws the markers. The icon spins while that request runs. The
point count then reads "updated just now." If the request fails, that
line says the map could not be refreshed.

Files:
  app/Http/Controllers/Admin/DashboardController.php
  resources/views/dashboard.blade.php
  tests/Feature/DashboardMapRefreshTest.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Start a public report and submit it with no GPS photo, no
   recording, and no shared location. "Please correct the following"
   lists the missing GPS capture once.
2. Capture a GPS photo, leave the location fields blank, and submit.
   The report is accepted and uses the coordinates from that capture.
3. Submit with no photo, but with a name and a phone number. The
   report is accepted for verification.
4. Open Live Map and turn on My location. The route panel leaves
   "Getting route…" and shows the distance and time to the nearest
   open center. The path is visible on the map.
5. Move with location still on. The blue position and the path
   update. The label does not return to "Getting route…" and stay
   there.
6. On the administrator, agency, and personnel dashboards, click the
   Situational Map refresh button. The icon spins, the markers reload,
   and the count reads "updated just now."
