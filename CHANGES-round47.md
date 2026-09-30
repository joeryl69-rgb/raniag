# RANIAG — Round 47 Changes

A public incident report can be sent with a type and a GPS photo or
video. Written details stay off until the reporter turns them on.
The location step is required when there is no capture, and it shows
the place on the map when there is one.

=====================================================================
1. Faster report
=====================================================================
The steps are Type, GPS camera, Location, then Contact. Title and
description are optional. They sit behind an Add incident details
switch, the same kind of switch as Report anonymously, and that
switch starts off. A blank description is stored as empty text.
A type plus a GPS capture, with no written description and no
manual coordinates, is enough to create the report.

Files:
  app/Http/Requests/Public/StoreIncidentReportRequest.php
  resources/views/public/report/create.blade.php
  public/js/public-report.js
  public/js/public-guide.js
  tests/Feature/Public/IncidentReportTest.php

=====================================================================
2. Location
=====================================================================
Without a GPS photo or video, Next stays on the location step until
the reporter shares a location. With a capture, that step is a
summary: the resolved place, the coordinates, and the map with a
pin on the capture. JO names the correction, the form shakes, and
the fields that failed are marked. The same treatment runs when a
type is missing, and again on submit when contact details are
required and left blank.

Files:
  resources/views/public/report/create.blade.php
  public/js/public-report.js
  public/js/public-guide.js

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Open Report an Incident. Add incident details is off. Turning it
   on shows the title and description.
2. Choose a type, skip the camera, and press Next on Location with
   no GPS. The form stays there, shakes, and JO points at the map.
3. Take a GPS photo or video. Location shows the place, the
   coordinates, and the map pin. Next continues to Contact.
4. Send a report with only a type and a GPS capture. It receives a
   tracking number.
