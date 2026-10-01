# RANIAG — Round 53 Changes

Support is no longer a menu item on the public site. Help stays on
the page, and JO's tour points at that button.

=====================================================================
1. Public menu
=====================================================================
The Support link is removed from the navbar on Home, Track Report,
Live Map, and the Community Dashboard. The headset button in the
corner still opens the Support Center. On the home page, Go to
Support Center is still in the questions section.

Files:
  resources/views/layouts/public.blade.php
  resources/views/components/help-fab.blade.php

=====================================================================
2. JO tour
=====================================================================
The Support step no longer looks for a menu item. It highlights the
help button. On the home page it highlights Go to Support Center,
because that link comes first, and the page scrolls so the button
is in view.

Files:
  public/js/public-guide.js

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Home, Track Report, Live Map, and Community Dashboard have no
  Support item in the menu.
- The headset button still opens Support.
- Start guided tour on the home page. The Support step highlights
  Go to Support Center, not a menu item.
