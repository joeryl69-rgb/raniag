# RANIAG — Round 51 Changes

Notifications stay a small panel, the sidebar shows how many are
unread, and reports referred outside Pamplona are listed beside the
situational map instead of disappearing.

=====================================================================
1. Notification panel
=====================================================================
Opening the bell no longer covers the page. The list is a card under
the bell, about 360 pixels wide, and it scrolls inside that card.
The same card is used on a phone and on a wide screen.

Files:
  resources/views/components/notification-bell.blade.php

=====================================================================
2. Unread indicator
=====================================================================
Notifications is a sidebar item for every signed-in role, with a red
count. The phone dock Alerts icon uses the same count. Opening a
notification from the bell marks that one read. Opening the
Notifications page marks the list read, and the count drops.

Files:
  resources/views/components/sidebar-nav.blade.php
  resources/views/components/mobile-dock.blade.php
  resources/views/components/notification-bell.blade.php
  resources/views/layouts/app.blade.php
  app/Http/Controllers/NotificationController.php

=====================================================================
3. Outside Pamplona, and the map fence
=====================================================================
The situational map stays framed on Pamplona. Reports marked outside
the area of responsibility are not pins on that map. They appear in
a list under the map — tracking number, type, and place — for the
admin desk and for the agency that was assigned. The municipal
boundary is a solid navy fence with a light fill. Barangay borders
are on by default, drawn as a dashed teal line, and named when the
pointer is over them.

Files:
  app/Services/SituationalMapService.php
  app/Http/Controllers/Admin/DashboardController.php
  app/Http/Controllers/Agency/DashboardController.php
  app/Http/Controllers/Personnel/DashboardController.php
  resources/views/dashboard.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- The bell opens a card. It does not cover the whole page.
- The sidebar Notifications item shows a count while something is
  unread. Opening Notifications clears that count.
- A report marked outside the area of responsibility is listed under
  the situational map, not as a pin inside Pamplona.
- The map shows a solid municipal outline and dashed barangay borders.
