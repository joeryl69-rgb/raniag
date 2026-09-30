# RANIAG — Round 48 Changes

The home page no longer repeats Support in the menu, and the lower
half reads as one sequence. The tracking lookup page shows the status
a report can move through. A report map pin uses the incident type.

=====================================================================
1. Home navigation
=====================================================================
Support is hidden from the home menu because the page already has a
Go to Support Center button. Every other public page still shows
Support in the menu. The home tour points at that button.

Files:
  resources/views/layouts/public.blade.php
  resources/views/public/home.blade.php

=====================================================================
2. How a report moves
=====================================================================
The duplicated how-it-works block is one row: JO, a short explanation,
and four steps in order. Questions sit beside the app download and
the support card, so those three pieces are no longer stacked through
the middle of the page.

Files:
  resources/views/public/home.blade.php

=====================================================================
3. Track report motion
=====================================================================
The lookup page opens with JO beside the form. Submitted, Assigned,
In progress, and Resolved take turns highlighting so the page is not
a single static card. The motion stops when the browser asks for
reduced motion.

Files:
  resources/views/public/track/index.blade.php

=====================================================================
4. Incident type on the report map
=====================================================================
The pin on the report location map uses the selected incident type's
icon and color, and it updates when the type changes.

Files:
  public/js/incident-map-icons.js
  public/js/public-report.js
  resources/views/public/report/create.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Home menu has no Support. Track Report still has Support.
- Home shows four steps, then updates, then questions beside the app
  and support cards.
- Track Report cycles the four status labels.
- A fire report pin is red; a flood report pin is blue.
