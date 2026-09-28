# RANIAG — Round 43 Changes

The text to a reporter is offered only when that person had to identify
themselves. The GPS camera records a one-minute video as well as a
photo. The administrator, the agency, and the assigned personnel see
the same text list. A recorded video keeps the map thumbnail and the
GPS lines inside the frame.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Text the reporter only when they are not anonymous
=====================================================================
A GPS camera photo or video lets the reporter stay anonymous, so the
text box is not shown for those cases.

When the original report has no GPS camera evidence, the case shows
"Reporter is not anonymous." That person had to leave contact details.
The message button is offered only then, and only when a phone number
is on the report.

Files:
  app/Models/Incident.php
  resources/views/components/responder-field-strip.blade.php

=====================================================================
2. One text list for every account on the case
=====================================================================
The personnel case was printing every SMS on the incident, including
office dispatch alerts, under the heading "SMS thread." The agency
case did not show that list at all, so two accounts assigned to the
same report did not see the same thing.

That dump is gone. Agency and personnel now share one list, titled
"Texts to the reporter." It includes only messages sent to the phone
number on the report. Office alerts stay out of it.

The administrator has the same notice, the same list, and a box to
send a text to that phone. A message sent from the admin case appears
in the list the agency and the assigned personnel already see.

Files:
  app/Models/Incident.php
  resources/views/components/responder-field-strip.blade.php
  resources/views/personnel/incidents/show.blade.php
  resources/views/admin/incidents/show.blade.php
  routes/admin.php

=====================================================================
3. One-minute GPS video
=====================================================================
Inside the same camera, swipe left for Video and right for Photo, or
tap Photo and Video. Those labels sit under the picture, not on top of
the GPS line. The camera does not restart when the mode changes, and a
swipe is ignored while a clip is recording. Recording stops at one
minute.

The saved video has the coordinates, place, time, and the small map
thumbnail burned in. The lines are sized from the height of the frame
and sit above the bottom edge, on a phone and on a desktop preview, so
the place and the time are not cut off.

Opening the camera without first tapping Use current location still
loads that map thumbnail once the phone gets a fix. The tile is loaded
from this site so it can be drawn into the recording.

The public report form, the agency case, and the personnel case use
this camera. A video can be played on the case and on the public
tracking page. Generated reports print photos only and leave the video
on the case file. Each evidence file may be up to 10 MB so a one-minute
clip can be saved.

Files:
  public/js/gps-camera.js
  public/css/public.css
  config/raniag.php
  resources/views/components/gps-camera.blade.php
  resources/views/public/report/create.blade.php
  resources/views/public/track/show.blade.php
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php
  resources/views/admin/incidents/show.blade.php
  resources/views/admin/reports/single_pdf.blade.php
  app/Http/Controllers/EvidenceFileController.php
  app/Http/Controllers/Public/TrackEvidenceController.php
  app/Http/Controllers/Public/HazardMapController.php
  routes/public.php

=====================================================================
4. Remember this device signs the account in from its icon
=====================================================================
The login page does not have a separate Remember me switch. Remember
this device on the verification screen is already on. Leaving it on
stores this browser for that account. Signing out does not clear it.

The next time that account appears under Choose an account, tapping
the icon signs in without the password and without another email code.
Removing the account from the list forgets the device, so the password
and the email code are required again. Turning Remember this device
off before verifying also leaves the password required.

Files:
  resources/views/auth/login.blade.php
  resources/views/auth/two-factor-challenge.blade.php
  app/Http/Controllers/Auth/AuthenticatedSessionController.php
  app/Services/TwoFactorService.php
  routes/auth.php

=====================================================================
5. Anonymous turns on with a GPS photo or video
=====================================================================
Report anonymously stays off until the report has a GPS camera photo
or video. Attaching one turns the switch on, so the name and contact
fields are cleared. Taking the evidence off turns the switch off again
and asks for a phone or email. The switch can still be turned off by
hand when a GPS photo or video is attached, if the reporter wants
their name on the report.

Files:
  public/js/public-report.js
  resources/views/public/report/create.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. Open an assigned case that has no GPS camera evidence. The field
   status says the reporter is not anonymous, and the text button is
   available when a phone number is on file.
2. Open a case that already has a GPS camera photo. The text box is
   not offered.
3. On the public report and on an assigned agency or personnel case,
   start the camera, swipe to Video, and record. It stops at one
   minute. The clip shows the map thumbnail, the place, and the time
   inside the frame.
4. Open the camera without tapping Use current location first. The map
   thumbnail appears once GPS arrives.
5. Download a generated case report. Photos are included. The video
   stays on the case and is not printed in that report.
6. Agency, personnel, and the administrator on the same case see
   "Texts to the reporter." Office dispatch texts are not in that list.
   A text sent by the administrator shows up for the assigned accounts.
7. Sign in, leave Remember this device on, and finish the email code.
   Sign out, then tap that account's icon. The password and the code
   are both skipped. Remove the account and the password is required
   again. The login page has no Remember me switch.
8. On a public report, add a GPS photo or video. Report anonymously
   turns on. Remove that evidence and it turns off.
