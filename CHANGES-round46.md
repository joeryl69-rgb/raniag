# RANIAG — Round 46 Changes

The administrator case map follows the assigned agency and personnel
to the incident. Reports keep the three downloads and add a decision
file the admin shapes. Printable document requests are reviewed one
request at a time. The public site and the staff portal share one
loading screen.

=====================================================================
1. Dispatch map
=====================================================================
The administrator live-location feed was crashing, so an assigned
agency never appeared on the case map. The feed now returns the
agency and each person sharing a location. Each one gets a route to
the incident. Agency, personnel, and administrator maps open framed
on that path. The administrator does not share their own GPS.

Files:
  app/Http/Controllers/Shared/LiveUnitsController.php
  app/Services/SituationalMapService.php
  public/js/raniag-dispatch-map.js
  public/js/raniag-mapbox.js
  resources/views/personnel/incidents/show.blade.php

=====================================================================
2. Reports
=====================================================================
The incident picture is not drawn on the Reports page. It is a
Decision report download. The admin picks the sections, this date
range, a second range to compare, and a projection for the next
period. The incident register stays the official row list.

Files:
  app/Http/Controllers/Admin/ReportController.php
  resources/views/admin/reports/index.blade.php
  resources/views/admin/reports/decision_pdf.blade.php
  resources/views/admin/reports/partials/_columns.blade.php

=====================================================================
3. Printable document requests
=====================================================================
An office asks for a case file. The request waits until an
administrator approves it, which builds the PDF and emails the
office, or rejects it. Each request is one row: tracking number,
office, sections, and note. Only a request still waiting shows the
comment and the two actions. A finished request shows View PDF.
The list does not refresh while a comment is being typed.

Files:
  resources/views/admin/document_requests/index.blade.php
  app/Http/Controllers/Admin/PrintableReportRequestController.php
  public/js/live-refresh.js

=====================================================================
4. Loading screen
=====================================================================
The public report form used a progress bar. The staff portal used a
pulsing logo. Both now use the same screen: a radar sweep around
the RANIAG mark, a contact blip, and a moving bar under the
message. The motion uses transform and opacity only. Reduced-motion
settings stop the sweep.

Files:
  public/css/raniag-loader.css
  resources/views/components/loading-overlay.blade.php
  resources/views/layouts/public.blade.php
  resources/views/layouts/app.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Submit a public report and confirm the radar loading screen
   appears, then the success page.
2. Download a staff report and confirm the same screen, with the
   file's own message.
3. Open Printable Document Requests. A waiting request has Approve
   and email PDF and Reject. A sent request has View PDF.
4. Open Reports, choose Decision report, set two date ranges, and
   download.
5. Open an assigned case as administrator. The map shows the agency
   and personnel who are sharing location, with a route to the
   incident.
