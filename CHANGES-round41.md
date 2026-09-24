# RANIAG — Round 41 Changes

Assigned personnel can open the case file again. The public tracking
progress bar uses the teal system colors, because the page stylesheet
was painting the old status colors on top of the updated CSS.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Personnel case file no longer crashes
=====================================================================
Opening /personnel/incidents/{id} compiled a broken script tag and
returned 500, so an assigned personnel login could not view the case.
The GPS settings script now prints the config the controller already
passes. The dispatch list also survives a case with no incident type.

Files:
  resources/views/personnel/incidents/show.blade.php
  resources/views/personnel/incidents/index.blade.php
  tests/Feature/AssignmentServiceTest.php

=====================================================================
2. Tracking progress bar colors actually change
=====================================================================
public.css already had the teal bar, but the public layout loaded a
second copy of those rules afterward and forced the old blue, yellow,
and orange status colors. That second copy is removed, so the bar
follows public.css.

Files:
  resources/views/layouts/public.blade.php
  public/css/public.css
  public/sw.js

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Assign a personnel account to a case and open it from that login.
2. Hard-refresh the public tracking page. The progress line and dots
   should be teal, not the old blue/yellow/orange status colors.
