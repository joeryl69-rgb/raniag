# RANIAG — Round 37 Changes

A second assigned agency can open the case. The report wizard keeps
Next at the bottom. Mark En route turns location on, On scene is
detected at the incident, and live maps keep a route with distance
and ETA.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Second assigned agency can open the incident
=====================================================================
Personnel can open a case assigned to them or to their agency. Agency
accounts are no longer blocked when an assignment timestamp looks older
than the incident. Dashboards and the case file list every assignment
on that incident.

A reporter phone that cannot be decrypted no longer crashes the
personnel case page (the 500 on /personnel/incidents/{id}).

Files:
  app/Models/Incident.php
  app/Policies/IncidentPolicy.php
  app/Repositories/IncidentRepository.php
  app/Http/Controllers/Agency/DashboardController.php
  app/Http/Controllers/Agency/IncidentController.php
  app/Http/Controllers/Personnel/DashboardController.php
  app/Http/Controllers/Personnel/IncidentController.php
  app/Http/Controllers/Personnel/ResolutionController.php
  app/Http/Controllers/Shared/ResponderFieldController.php
  resources/views/personnel/incidents/show.blade.php
  resources/views/admin/incidents/show.blade.php
  resources/views/components/confirm-action-modal.blade.php
  resources/views/components/responder-field-strip.blade.php

=====================================================================
2. Report wizard stays usable without scrolling up
=====================================================================
Next, Back, and Submit sit in a bar at the bottom of the report form.
Changing steps no longer jumps the page back to the top.

Files:
  resources/views/public/report/create.blade.php
  public/js/public-report.js
  public/css/public.css

=====================================================================
3. Mark En route starts GPS; On scene is automatic
=====================================================================
The My location button is removed. Mark En route is the permission
prompt and the first saved position. After that, the device keeps
sharing about every 4 seconds.

On scene is set automatically when the device is within about 180
meters of the incident. Accept & Acknowledge is a fixed red bar and
does not require location.

The live pin is a person, not a truck.

Files:
  public/js/raniag-location-ping.js
  public/js/raniag-dispatch-map.js
  public/css/public.css
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php
  resources/views/components/responder-field-strip.blade.php
  public/sw.js

=====================================================================
4. Route line, distance, and ETA stay visible
=====================================================================
A failed Mapbox refresh no longer wipes the line. When the road route
cannot be loaded, the map draws a straight line and an estimated time.
The agency case map and the public tracking page both show the unit
name, distance, and ETA. The pin slides as new GPS arrives. The
nearest-center map uses the same straight-line estimate.

Files:
  public/js/raniag-mapbox.js
  public/js/raniag-dispatch-map.js
  public/js/public-track-map.js
  public/js/public-hazard-map.js
  resources/views/public/track/show.blade.php

=====================================================================
5. Red new-incident alert
=====================================================================
The dashboard banner stays up until it is dismissed. Opening the
dashboard no longer marks the incident seen in the background, so a
new report still pulses.

Files:
  resources/views/dashboard.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Log in as the second assigned agency or its personnel and open the
   incident. The case file should load.
2. Public report: Next stays at the bottom on each step.
3. Accept without location. Then Mark En route, allow the prompt, and
   confirm the person pin and the distance/ETA on the case map and on
   the public tracking page. Move the device and confirm the pin moves.
4. Near the incident, the phase should become On scene without a tap.
5. Live map, Route to nearest center: a line and a time should show
   even if the road route fails.
6. Dashboard: a new incident shows the red banner until dismissed.
