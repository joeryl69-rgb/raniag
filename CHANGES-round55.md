# RANIAG — Round 55 Changes

The staff command center is quieter, summary cards use one layout on
the sidebar pages that already have counts, the public tour close
button can be clicked, and a hazard area uses its incident-type color
on both the staff map and the public Live Map.

=====================================================================
1. Command center
=====================================================================
The four summary cards sit above the situational map. The map header
is a plain bar instead of a blue band, and the new-incident banner no
longer pulses. Each card leads with the number and keeps a small icon
in the corner.

Files:
  resources/views/dashboard.blade.php

=====================================================================
2. Summary cards on sidebar pages
=====================================================================
The same card layout is used where a page already has a count:
incidents, hazard areas, case documents, incident types, QR posters,
reports, accounts, personnel roles, document requests, SMS alerts,
audit trails, feedback, announcements, and notifications. Agency and
personnel dispatches, document requests, and resolved reports use it
too. Support stays a form and has no count strip.

Files:
  resources/views/components/kpi-strip.blade.php
  public/css/public.css
  app/Http/Controllers/Admin/IncidentController.php
  resources/views/admin/incidents/index.blade.php
  resources/views/admin/hazard/index.blade.php
  resources/views/admin/incident_documents/index.blade.php
  resources/views/admin/incident_types/index.blade.php
  resources/views/admin/qr_posters/index.blade.php
  resources/views/admin/reports/index.blade.php
  resources/views/admin/agencies/index.blade.php
  resources/views/admin/personnel_roles/index.blade.php
  resources/views/admin/document_requests/index.blade.php
  resources/views/admin/sms-logs/index.blade.php
  resources/views/admin/audit-logs/index.blade.php
  resources/views/admin/feedback/index.blade.php
  resources/views/admin/announcements/index.blade.php
  resources/views/notifications/index.blade.php
  resources/views/agency/incidents/index.blade.php
  resources/views/agency/document_requests/index.blade.php
  resources/views/agency/archived_reports/index.blade.php
  resources/views/personnel/incidents/index.blade.php

=====================================================================
3. Guided tour close button
=====================================================================
The X closes the tour from the first step, including when it is
started from Start guided tour. The step counter no longer sits on
top of the button.

Files:
  public/js/public-guide.js
  public/css/public.css

=====================================================================
4. Hazard area color
=====================================================================
The color chip is the color of the next area for the type selected
in the dropdown. Areas already saved keep their own type color, and
that is the color residents see on the public Live Map. The map key
names each saved area with its own swatch. A Flood area stays blue
because Flood’s color is blue. An Evacuate area uses the chip color
on both maps.

Files:
  app/Models/HazardZone.php
  app/Http/Controllers/Admin/HazardEvacController.php
  resources/views/admin/hazard/index.blade.php
  public/js/public-hazard-map.js

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Command Center: summary cards are above the map, and the map header
  is not a blue bar.
- Incidents, Feedback, and Announcements: the same summary cards
  appear above the list.
- Public home: Start guided tour, then click the X on step 1 of 6.
  The tour closes.
- Hazard map: the chip names the selected type. A saved Flood area
  stays blue on the staff map and on the public Live Map. A new area
  of another type uses that type’s color on both maps.
