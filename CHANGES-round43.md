# RANIAG — Round 43 Changes

The field text box only appears when the reporter had to identify
themselves. The GPS camera can record a one-minute video, on the
public form and on a case assigned to an agency or personnel account.
Printed reports still use photos only.

Hostinger deploys when this lands on `main`.

=====================================================================
1. Text the reporter only when they are not anonymous
=====================================================================
A GPS camera photo or video lets the reporter stay anonymous, so the
text box is not shown for those cases.

When the original report has no GPS camera evidence, the assigned
office sees "Reporter is not anonymous." That person had to leave
contact details. The message button is offered only then, and only
when a phone number is on the report.

Files:
  app/Models/Incident.php
  resources/views/components/responder-field-strip.blade.php

=====================================================================
2. One-minute GPS video
=====================================================================
Inside the same camera, swipe left for Video and right for Photo, or
tap the labels. The camera does not restart when the mode changes, and
a swipe is ignored while a clip is recording. Recording stops at one
minute. The saved file has the coordinates, place, and time burned in.

The public report form, the agency case, and the personnel case use
this camera. A video can be played on the case and on the public
tracking page. Generated reports print photos only and leave the video
on the case file.

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
   minute and the clip shows the GPS line.
4. Download a generated case report. Photos are included. The video
   stays on the case and is not printed in that report.
5. The text list under Live field status is "Texts to the reporter".
   Agency and personnel assigned to the same case see the same list.
   Office dispatch texts are not mixed in.
6. On a phone, Photo and Video sit under the picture, not on top of
   the GPS line. A recorded video includes the small map thumbnail.
7. Opening the camera without first tapping Use current location still
   loads the map thumbnail once GPS arrives.
8. The GPS lines on a video sit inside the frame on a phone and on a
   desktop preview, with space under them so they are not cut off.
9. The administrator case page shows the same "Reporter is not
   anonymous" notice, the same text list, and can send a text to that
   phone. Agency and personnel see that message on the case.
