# RANIAG — Round 36 Changes

Agency and personnel can turn on this device’s location on the case map.
While they are en route, the truck pin moves on that map, on the admin
case file, and on the public tracking page.

Hostinger deploys when this lands on `main`.

=====================================================================
1. My location button (agency and personnel)
=====================================================================
Browsers often ignore GPS that starts by itself. The case-file route map
now has a My location button for agency and personnel. Tapping it is the
permission prompt. After En route or On scene, that position is saved
about every 4 seconds and the pin follows the device.

Admin does not share a personal GPS from this button. Admin watches the
same moving pin on the incident case file.

The field-status panel on both agency and personnel says to tap My location.

Files:
  public/js/raniag-dispatch-map.js
  public/js/raniag-location-ping.js
  public/css/public.css
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php
  resources/views/components/responder-field-strip.blade.php

=====================================================================
2. The pin moves instead of being redrawn in place
=====================================================================
Case maps (admin, agency, personnel) and the public tracking map keep
the same marker and slide it to each new coordinate. The driving line
refreshes about every 12 seconds. The camera fits the trip once, then
lets the pin travel. After My location, the responder’s own map pans
with them.

Those maps ask for a new position every 5 seconds.

Files:
  public/js/raniag-dispatch-map.js
  public/js/public-track-map.js
  resources/views/admin/incidents/show.blade.php
  resources/views/public/track/show.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Agency or personnel case file: tap My location, allow the prompt,
   mark En route. The truck pin should leave your current spot and the
   map should pan as you move.
2. Public tracking page for that report: the same agency or personnel
   name should slide toward the incident, with distance and time.
3. Admin case file for that incident: the same moving pin, without a
   My location button.
