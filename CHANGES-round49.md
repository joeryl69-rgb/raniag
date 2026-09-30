# RANIAG — Round 49 Changes

The home page now shows a report as one path across an incident desk,
and the tracking page is a case lookup instead of a plain form.

=====================================================================
1. Home incident desk
=====================================================================
How a report moves is a dark desk with four stations on one line:
file, number, assign, watch. A light runs along that line. Updates
read as a dated log. Questions stay beside the app and support cards.
Support stays off the home menu.

Files:
  resources/views/public/home.blade.php

=====================================================================
2. Case lookup
=====================================================================
Track Report opens as the incident desk: a radar, the four statuses
a case can reach, and a ticket for the tracking number. The active
status moves down the path. The motion stops when the browser asks
for reduced motion.

Files:
  resources/views/public/track/index.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Home shows the dark desk with four steps on one line.
- Track Report shows the radar, the status path, and the case ticket.
- Looking up a number still opens the status page.
