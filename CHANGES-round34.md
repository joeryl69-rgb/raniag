# RANIAG — Round 34 Changes

Follow-up after the Mapbox token was added. Tiles loaded, but the driving
path and the responder pin still did not. Two separate bugs.

Commit on main: `b71c749` (this changelog is the commit after it).
Hostinger deploys automatically when this lands on `main`.

=====================================================================
1. Mapbox routes were blocked by the site security policy
=====================================================================
`Content-Security-Policy` allowed Mapbox only as images (`img-src https:`),
so the basemap rendered. The driving/walking line is a `fetch()` to
`https://api.mapbox.com`, and `connect-src` was `'self'` only. The browser
blocked that request, which is why:

  - Public tracking showed the incident pin and the text
    "1 responding unit approaching", with no path.
  - The landing Live Map said
    "Could not load Mapbox route. Try again or switch Walk/Drive."

Fix: `app/Http/Middleware/SecurityHeaders.php` now allows
`https://api.mapbox.com` and `https://events.mapbox.com` on `connect-src`.

=====================================================================
2. En-route GPS is a live watch, with a visible status
=====================================================================
Marking En route used a slow one-shot ping and hid permission errors.
The responder device now watches GPS while the phase is En route or
On scene and posts the position about every 12 seconds.

Case Action Control shows a status line:

  - waiting for the browser location prompt
  - green when sharing is on
  - red if location is blocked or GPS cannot be read

The device that taps En route must tap Allow. The public tracking page
cannot invent that position.

Files:
  public/js/raniag-location-ping.js
  resources/views/components/responder-field-strip.blade.php
  resources/views/agency/incidents/show.blade.php
  resources/views/personnel/incidents/show.blade.php

=====================================================================
3. Responder pin on the case map and the public tracking map
=====================================================================
The old unit marker was a 14px dot, easy to miss under the incident pin.
Both maps now draw a blue truck pin with the agency name, then the
Mapbox driving line, distance, and ETA.

If GPS has not arrived yet, the status says so instead of
"1 responding unit approaching" with nothing on the map.

Files:
  public/js/public-track-map.js
  public/js/raniag-dispatch-map.js
  public/css/public.css

=====================================================================
Verify after Hostinger deploy
=====================================================================
1. GitHub Actions "Deploy to Hostinger" for this push is green.
2. Hard-refresh the agency case file. Mark En route. Allow location.
   The status line turns green.
3. Open the public tracking page for that incident. A labeled truck pin
   and a driving line to the incident should appear within about 15 seconds.
4. On the public Live Map, turn on My location and choose Drive.
   The route to the nearest center should draw, without
   "Could not load Mapbox route."
