# RANIAG — Round 49 Changes

The public home page and the Track Report page are rebuilt so they
read as the MDRRMO incident desk. A report is one path, and a
tracking number is a case.

This replaces the layout from round 48. Support is still hidden on
the home menu only. The report-map pin from round 48 is unchanged.

=====================================================================
1. Home incident desk
=====================================================================
"How a report moves" is one dark desk, not four separate cards.
JO, a one-sentence explanation, and the guided-tour button sit at
the top. Under that, four stations share one line:

  1. File — a type and a GPS photo. Written details are optional.
     Without a photo, the reporter leaves a number.
  2. Number — the tracking number is issued when the report is in.
  3. Assign — MDRRMO Pamplona sends it to the responder for that
     part of Pamplona.
  4. Watch — submitted, assigned, in progress, or resolved, on the
     site rather than by calling the office.

A light runs along the line from File toward Watch. On a narrow
screen the line is hidden and the four stations stack. The motion
stops when the browser asks for reduced motion.

Support stays out of the home menu because Go to Support Center is
already on the page. Track Report, Live Map, Community Dashboard,
and the other public pages still show Support. The home tour points
at the support button on the page.

Files:
  resources/views/layouts/public.blade.php
  resources/views/public/home.blade.php

=====================================================================
2. Updates, questions, and help
=====================================================================
Announcements are a dated log. Each item shows the day, the badge,
the title, and a short body, with a blue rail on the left. Two
announcements share the row instead of leaving an empty third card.

Questions stay in the accordion. The app download and Go to Support
Center sit in the column beside those questions, so the help actions
are not a separate stack in the middle of the page.

Files:
  resources/views/public/home.blade.php

=====================================================================
3. Case lookup
=====================================================================
Track Report is no longer a centered form with a mascot beside it.
The page is a desk:

  - A radar marks the incident desk.
  - The heading explains that the receipt number is the case.
  - Submitted, Assigned, In progress, and Resolved are a vertical
    path. The active step moves down that path.
  - The tracking number is typed on a case ticket. The field keeps
    the name tracking_number, and Look up still posts to the same
    status page.
  - The ticket notes that no account is required, and it links to
    a new report if the number is lost.

The radar sweep and the moving status stop when the browser asks
for reduced motion. On a narrow screen the ticket sits under the
status path.

Files:
  resources/views/public/track/index.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Home menu has Home, Track Report, Live Map, Community Dashboard,
  Staff Login, and Report an Incident. It does not list Support.
- Track Report still lists Support in the menu.
- Home shows the dark desk with File, Number, Assign, and Watch on
  one line, then the dated update log, then the questions beside
  the app and support cards.
- Track Report shows the radar, the four-step path, and the case
  ticket. Opening a number still reaches the status page.
