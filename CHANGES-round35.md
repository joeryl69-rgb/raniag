# RANIAG — Round 35 Changes

The agency case page said "Live location is on" but the map stayed on
"Checking responder GPS…", and the public tracking page stayed on
"Loading responders…". GPS was being read. The maps never drew it.

Commit this file ships with the fix. Hostinger deploys from `main`.

=====================================================================
1. The responder pin is drawn from the phone immediately
=====================================================================
Marking En route already watched GPS. That position was only sent to the
server, and the map waited for a second request that never updated the
status line. The case map now plots the truck pin and the driving line
as soon as the browser has a location, and keeps that pin if the server
list comes back empty.

Files: public/js/raniag-location-ping.js, public/js/raniag-dispatch-map.js

=====================================================================
2. Public tracking can load the responder feed
=====================================================================
The units request required a session flag that the tracking page poll
was not reliably sending, so the map never left "Loading responders…".
The page now passes a lookup token on that request (still rejected
without the token or a verified lookup). The first paint also uses the
units already loaded with the page. A failed request shows the HTTP
status instead of spinning forever.

The 8-second HTML refresh on the tracking page was removed so it cannot
wipe the map.

Files: app/Http/Controllers/Public/IncidentTrackController.php,
public/js/public-track-map.js, resources/views/public/track/show.blade.php

=====================================================================
3. Agency GPS is included even when the assignment names one person
=====================================================================
Live units only searched the whole agency when `assigned_to` was empty.
If the row pointed at one account, the phone that tapped En route was
ignored. Every active user in the assigned agency with a fresh GPS ping
is now included.

File: app/Services/SituationalMapService.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Hard-refresh the agency case file. With En route and location allowed,
   a truck pin and a driving line should appear on that map within a
   few seconds. The status line should leave "Checking responder GPS…".
2. Open the public tracking page for the same report. The truck pin and
   route should show Bureau of Fire Protection, not stay on
   "Loading responders…".
3. If a feed still fails, the status line names the HTTP code instead
   of staying on the loading text.
