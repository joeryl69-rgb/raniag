# Round 61 — Public navigation, track handoff, and theme consistency

- Removed the public page handoff. Links on Home, Track Report, Live Map, Community Dashboard, Support, and Report an Incident now load immediately. The old path held the click for 80ms, then slid the new page down as it appeared, which is what made each screen knock on the way in.
- Stopped the load-time slide on content that is already on screen. Headlines, the track desk, and the support form stay in place. Sections below the fold can still fade in, without the vertical jump.
- Rebuilt the 404 page in the case-desk language: dark operations panel, RANIAG mark, radar, JO, and actions back to Home or Track a report. It follows the saved light or dark theme.
- Track This Report now opens the lookup form with the number already filled in, and lands on that form instead of the top of the status list. A note on the form points at Open case updates. On a phone, a bar stays at the bottom until that button is on screen, then it goes away.
- Moved JO on Track Report out of the status steps on tablet and phone. The mascot sits in its own band above the steps. On a wide screen the radar and JO stay pinned to the top of the case board, so they no longer ride up or down with the height of the panel.
- Pinned the home landmark radar to the top-right of the photo on both desktop and phone. It no longer drops toward the coordinate card on a small screen.
- Placed the RANIAG mark in the home radar, in the center of the field-tool radar, beside the field-tool label, and on the human-support card.
- Stacked the field-tool and human-support cards as a pair with a 16px gap. They no longer stretch apart to fill the height of the updates column.
- The cursor glow now follows the pointer on every public page and on Staff Login, in both light and dark. Touch and reduced-motion settings leave it off.
- Removed the second Updates and announcements block on Home. One list remains, inside the public-information desk, and that is the list the live refresh updates. The earlier copy used the same element id, so the visible list never received the refresh.
- Closed the strip between the home briefing and the footer. That strip was the main area’s bottom padding, not a designed gap.
- Staff login’s “Remember this device” label and the sentence under it now use light text on the dark form. Support timeline text, tips, category labels, the send-confirmation dialog, and light-gray panels also switch with the theme so dark type is not left on a dark surface.
