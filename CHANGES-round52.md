# RANIAG — Round 52 Changes

The notification card stays inside the screen. It no longer runs off
the right edge or sits on top of the map layers.

=====================================================================
1. Notification card
=====================================================================
The card is measured against the visible screen and shifted left until
its right edge clears the margin. Opening it closes the map layer
box so the two panels are not stacked. The title and message wrap
instead of being cut off mid-word.

Files:
  resources/views/components/notification-bell.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Open the bell on a phone-width window. The card is fully on screen.
- The map layer box closes when the bell opens.
- A notification title is readable on one or two lines.
