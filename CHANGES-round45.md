# RANIAG — Round 45 Changes

The public home and guided tour use the new JO photos and the
Pamplona landmark. Staff hazard mapping, QR posters, reports, and
case paperwork each have their own screen. Dark mode locks the color
themes and uses one dark surface. The command center and the public
Live Map both stay on open incidents inside Pamplona. An assigned
agency shows on the dispatch map, and the login code email uses the
same RANIAG layout as the other messages.

These changes are already on `main` through `6c03d42`.

=====================================================================
1. Home page, landmark, and JO
=====================================================================
The home page uses one centered How it works row instead of the
repeated cards. The Pamplona landmark is on the home and login
screens. JO is the new photo set on every public screen, including
the first step of the guided tour. The mascot is large enough to
read, the studio background no longer leaves a white rectangle, and
the floating pose stays one piece. The tour stays on the screen it
is explaining, including on a phone.

Files:
  public/css/public.css
  public/css/auth.css
  public/images/pamplona-landmark.jpg
  public/images/guide/jo-*.jpg
  public/js/public-guide.js
  resources/views/public/home.blade.php
  resources/views/layouts/public.blade.php
  resources/views/public/hazard/map.blade.php
  resources/views/public/report/create.blade.php
  resources/views/public/report/success.blade.php

=====================================================================
2. Announcement edit
=====================================================================
Choosing Edit on a published announcement opens that item in the
form and saves it, instead of leaving the form on a new draft.

Files:
  resources/views/admin/announcements/index.blade.php
  tests/Feature/AnnouncementEditTest.php

=====================================================================
3. Staff form saves
=====================================================================
The loading state waits until the browser has sent the form, so a
save is not dropped. The save button stays enabled while the request
is posted, which stops Chrome from discarding the submission.

Files:
  resources/views/layouts/app.blade.php
  resources/views/auth/login.blade.php
  resources/views/components/auth-split.blade.php
  resources/views/admin/feedback/index.blade.php
  resources/views/admin/incident_types/index.blade.php
  resources/views/admin/personnel_roles/index.blade.php

=====================================================================
4. Hazard map, QR posters, and reports
=====================================================================
Hazard areas and shelter pins share one map. The area type comes
from Incident Types. The public advisory is edited later, not on
create. The evacuee registry is its own tab. The sidebar says
Hazard map.

QR posters are a table. Edit opens a modal. Print still uses the
preview.

Reports is one file choice, a date range, and one download. Extra
filters stay collapsed. The sidebar says Reports.

Files:
  app/Http/Controllers/Admin/HazardEvacController.php
  app/Http/Controllers/Admin/QrPosterController.php
  resources/views/admin/hazard/index.blade.php
  resources/views/admin/qr_posters/index.blade.php
  resources/views/admin/reports/index.blade.php
  resources/views/components/sidebar-nav.blade.php
  routes/admin.php
  public/css/public.css
  public/js/public-hazard-map.js

=====================================================================
5. Case documents are separate from the live incident
=====================================================================
Incidents stays the live case. Case Documents is the list and the
file for the four paper forms. Opening a case file no longer opens
the incident screen. The incident page only links across to the
paperwork.

Files:
  app/Http/Controllers/Admin/IncidentDocumentController.php
  app/Enums/IncidentDocumentType.php
  resources/views/admin/incident_documents/index.blade.php
  resources/views/admin/incident_documents/show.blade.php
  resources/views/admin/incidents/index.blade.php
  resources/views/admin/incidents/show.blade.php
  routes/admin.php

=====================================================================
6. GPS video and case-file removal
=====================================================================
A GPS recording keeps the full minute, up to the one-minute limit,
and the place stamp is in the saved file. The staff player shows
GPS Video or GPS Photo and does not crop the bottom of the frame.
Removing a case-file photo is a button beside the thumbnail, not a
control sitting on the picture.

Files:
  public/js/gps-camera.js
  resources/views/admin/incident_documents/show.blade.php
  resources/views/admin/incidents/show.blade.php
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php

=====================================================================
7. Appearance and dark mode
=====================================================================
Theme samples show the sidebar, page, and button. Text size is
previewed in a sample, not by resizing the whole portal while the
slider moves. Follow system appearance is gone.

While dark mode is on, the color themes are disabled. Turning dark
mode off unlocks the saved theme. Tables, inputs, selects, and
dashboard cards use the same dark surface as the rest of the page.
The sidebar collapse button sits fully outside the sidebar edge.

Files:
  app/Http/Controllers/AppearanceSettingController.php
  app/Support/ThemePresets.php
  resources/views/settings/appearance.blade.php
  resources/views/layouts/app.blade.php
  public/css/public.css

=====================================================================
8. Command center
=====================================================================
Administrator, agency, and personnel still share one dashboard.
Open incidents inside Pamplona are the decision list, the barangay
counts, the priority bars, and the type bars. Status uses Submitted,
In progress, Resolved, and Closed. Reports over the last six weeks
stay as their own chart. On-time resolution replaces the SLA label.
Agency accounts see Active dispatches. Personnel accounts see My
assignments.

Files:
  resources/views/dashboard.blade.php
  app/Http/Controllers/Admin/DashboardController.php

=====================================================================
9. Public Live Map stays inside Pamplona
=====================================================================
Open-report counts and the colored barangays use Pamplona's 18
barangays only. A report marked outside the area of responsibility,
including a neighboring name such as Langagan, is left off the Live
Map and off the command-center barangay list.

Each shelter has its own pin color, on create and on a saved
shelter. Dropping a pin keeps the staff map on Pamplona instead of
zooming until the tiles disappear.

Risk, Places, and Route are separate tabs. Places lists the hazard
zones and the open evacuation centers. The layer names match the
page: Hazard zones, Evacuation, Barangay risk, and My location.
The legend matches those layers.

Files:
  app/Services/SituationalMapService.php
  app/Http/Controllers/Admin/HazardEvacController.php
  app/Models/EvacuationCenter.php
  database/migrations/2026_09_29_120000_add_color_to_evacuation_centers.php
  resources/views/admin/hazard/index.blade.php
  resources/views/public/hazard/map.blade.php
  public/js/public-hazard-map.js
  public/css/public.css
  routes/admin.php

=====================================================================
10. Dispatch map and login code email
=====================================================================
The administrator incident map no longer stops on "Responder feed
failed (500)" after an agency is assigned. The map names that
agency. The route is drawn from a shared responder location to the
incident, including a location shared during the response. If nobody
has marked En route, the map says the route appears when a responder
shares location.

The login verification email uses the RANIAG header, a code block,
and the ten-minute expiry, in the same shell as the password-reset
message.

Files:
  app/Http/Controllers/Shared/LiveUnitsController.php
  app/Services/SituationalMapService.php
  public/js/raniag-dispatch-map.js
  resources/views/emails/auth/login-otp.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Open the public home page. JO is a photo, How it works is one
   row, and the landmark is visible. Start the guided tour and
   confirm the first step shows JO.
2. Edit a published announcement and save it.
3. On Appearance, turn dark mode on. Color themes are disabled.
   Turn it off and the previous theme can be selected again. Open
   Incidents and confirm the table matches the dark page.
4. On Hazard map, set a shelter pin color, save, and confirm the
   public Live Map uses that color. Confirm Langagan is not in
   Open reports by barangay.
5. Open Places on the Live Map and confirm the hazard zone and the
   open evacuation center are listed there, not stacked above the
   tabs.
6. On the command center, confirm the decision list, barangay
   counts, and type bars are open Pamplona incidents.
  7. Assign an agency to a case and open it as administrator. The map
    names the agency. After En route with location shared, the route
   line is drawn.
8. Sign in with a code. The email has the RANIAG header and a
   single verification code.
