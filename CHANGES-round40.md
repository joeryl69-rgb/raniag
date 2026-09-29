# RANIAG — Round 40 Changes

Personnel assigned directly to a case can now open it again, and
multi-agency cases stay accessible to the correct agency login. The
public tracking page now redirects to a normal status URL, so a refresh
no longer resubmits the old tracking form and triggers 419 Page Expired.
The tracking progress bar has also been refreshed to a cleaner,
more polished system palette.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Personnel access is restored for assigned cases
=====================================================================
The incident access policy was still relying on stale relationship data
in a few places, which could hide a valid assignment for a personnel
account. Access is now resolved against the assignments table for the
logged-in user and agency, so an assigned personnel user can open their
incident even when a second agency is also assigned.

Files:
  app/Policies/IncidentPolicy.php
  app/Http/Controllers/Agency/IncidentController.php
  app/Http/Controllers/Personnel/IncidentController.php
  tests/Feature/AssignmentServiceTest.php

=====================================================================
2. Tracking refresh no longer expires
=====================================================================
A successful report lookup now redirects to the normal status page at
/track/{tracking number}. Refreshing that page is a normal GET and no
longer resubmits the original form, so the browser no longer hits 419
Page Expired after a valid lookup.

Files:
  app/Http/Controllers/Public/IncidentTrackController.php
  routes/public.php
  resources/views/public/track/index.blade.php

=====================================================================
3. Tracking progress bar is cleaner and more polished
=====================================================================
The horizontal status bar was visually too harsh and generic for the
RANIAG system. It has been redesigned with a cleaner teal-blue palette,
more balanced connector flow, and better dot spacing so the progress
layout reads as part of the app instead of a stretched standalone bar.

Files:
  public/css/public.css
  resources/views/public/track/show.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Assign a personnel account to a case and log in as that user.
2. Assign a second agency to the same case and confirm the second agency
   can still open the case.
3. Look up a report, then refresh. The status page should remain open
   without any 419 Page Expired error.
4. Review the public tracking page on desktop and confirm the progress
   bar feels cleaner and better aligned with the system styling.
