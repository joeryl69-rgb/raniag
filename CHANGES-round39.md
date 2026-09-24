# RANIAG — Round 39 Changes

Multiple assigned agencies now open correctly for the matching agency
login, and personnel retain access when assigned directly or through
their agency. Public tracking opens a normal status page so refreshes
no longer trigger a 419 Page Expired. The tracking progress bar is
also redesigned to sit cleanly with the system layout.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Agency and personnel access is restored for multi-agency cases
=====================================================================
The case-view gate was checking the wrong assignment context for a
later-assigned agency. Access now resolves against the assignments
table directly for the logged-in agency, while personnel access still
works when they are assigned to the case or to their agency.

Files:
  app/Policies/IncidentPolicy.php
  app/Http/Controllers/Agency/IncidentController.php
  app/Http/Controllers/Personnel/IncidentController.php
  tests/Feature/AssignmentServiceTest.php

=====================================================================
2. Tracking refresh no longer expires
=====================================================================
The public lookup was leaving the browser on the POST submission and
refreshing that page re-sent the old token. The flow now redirects to
/track/{tracking number} after a valid lookup, so a refresh is a
normal GET load and no longer shows 419 Page Expired.

Files:
  app/Http/Controllers/Public/IncidentTrackController.php
  routes/public.php
  resources/views/public/track/index.blade.php

=====================================================================
3. Tracking progress bar is aligned to the system
=====================================================================
The horizontal status bar was visually cramped and misaligned with the
report layout. It has been redesigned with cleaner spacing, better dot
positioning, and a more consistent connector so it reads as part of the
RANIAG interface instead of a stretched generic control.

Files:
  public/css/public.css
  resources/views/public/track/show.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Assign a second agency to a case and open it with the second agency's login.
2. Assign a personnel account to the same incident and verify that the
   person can open the case.
3. Track a report, then refresh. The report should remain open and not
   show 419 Page Expired.
4. Check the public status page on desktop: the progress bar is aligned,
   balanced, and does not crowd the cards or labels.
