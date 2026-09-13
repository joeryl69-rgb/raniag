RANIAG changelog — this round (bug fixes + Tagalog/EN removal + seeder cleanup)

HOW TO APPLY
Extract this zip's `raniag/` folder directly on top of your existing project folder
(same name, same location) so each file just overwrites its counterpart at the same
path — nothing else in your project is touched, and your database is untouched by
any of this (no migrations here).

After copying the files:
    php artisan view:clear
    php artisan optimize:clear

=====================================================================
1) Re-dispatch let you dispatch the same agency/personnel twice
=====================================================================
Root cause: the "Select Additional Government Branch(es) or Personnel" checklist
on the admin incident page always listed every active agency/personnel, including
ones already assigned to that incident.

Fix: Admin/IncidentController@show now excludes any agency/personnel that already
has an assignment on this incident (at any point, not just currently active) from
every dispatch/re-dispatch checklist. If everyone active has already been
dispatched, the re-dispatch panel now shows a plain message instead of an empty
or duplicate-prone checklist.

Check: open an incident that's been dispatched to one agency, open "Re-dispatch /
Add Another Agency", and confirm that agency no longer appears in the list —
only agencies/personnel not yet assigned should show.

=====================================================================
2) GPS Camera not pinpointing location for agency/personnel users
=====================================================================
Root cause, confirmed in the code: gps-camera.js only ever resolves an address
through `window.RANIAG_MAP_API` or `window.RANIAG_LOCATION_API`. Both of those
are defined exclusively inside public/js/public-report.js — the public report
form's own script — which never loads on the agency/personnel incident pages.
So on those pages, address resolution silently never ran at all: the watermark
was stuck on "Resolving address…" forever, and the Capture button stayed
disabled forever, because it waits on a resolved barangay/municipality that was
never going to arrive.

Fix: added a small, self-contained fallback reverse-geocode directly in
gps-camera.js, used only when neither of those two globals exists. It hits the
same Nominatim endpoint the public form uses and dispatches the same
`raniag:location-resolved` event, so the rest of the file needs no other
changes. Also has a network-failure fallback (falls back to raw coordinates)
so a flaky connection can't leave the operator stuck indefinitely either.

Check: as an agency or personnel user, open an incident, open GPS Camera, and
confirm the address line resolves to something real within a few seconds
(instead of staying on "Resolving address…"), and that Capture Photo becomes
clickable.

=====================================================================
3) GPS Camera design inconsistency (agency/personnel vs public)
=====================================================================
Looked closely at both: the agency/personnel side already uses the shared
`<x-gps-camera />` component, and it was already structurally identical to the
public report form's camera (same modal, same watermark, same layout) — so if
what's live on your server still looks different/older, that's very likely a
stale compiled-view cache rather than a real markup difference (same class of
issue as the earlier 500 error) — the `view:clear` above should resolve it.

One real, smaller inconsistency was found and fixed: the "Start Camera" button
used different text and a different button style (outline) in the shared
component than on the public form (solid). Both now match exactly.

=====================================================================
4) "Processing, please wait..." button turning green
=====================================================================
Root cause: buttons like "Resolve Incident" are intentionally green
(btn-success) even before you click them — that part's by design. The bug is
that the loading state kept that green color, so a still-pending action looked
like it had already succeeded.

Fix: setButtonLoading() in layouts/app.blade.php now swaps whatever color class
a button has (btn-success, btn-primary, etc.) to a neutral btn-secondary for
the duration of the "Processing…" state, and restores the original color the
moment it resets. This applies to every submit button system-wide, not just
one specific form.

Check: click "Resolve Incident" (or any colored submit button) and confirm it
turns gray with a spinner while processing, then returns to its normal color
afterward — it should never show solid green while still in progress.

=====================================================================
5) Case Documents Repository — awkward sizing/alignment
=====================================================================
Root cause: the delete button on each document thumbnail was sized only via a
single custom CSS class (`.rg-docthumb-delbtn`), which has the exact same CSS
specificity as Bootstrap's own `.btn`/`.btn-sm` classes on the same element —
so which one actually won the 20px sizing came down to source order, and lost
on some pages, inflating it into the oversized, egg-shaped red circle seen in
your screenshots. The Take Photo / Upload File buttons below also only stacked
under one narrow breakpoint instead of aligning consistently everywhere.

Fix: forced the delete button's size/shape with `!important` so Bootstrap's
classes can never win regardless of source order, added explicit
`box-sizing: border-box`, and made Take Photo / Upload File always stack
full-width consistently on every screen size (not just under 420px).

Check: open Case Documents Repository on both mobile and desktop and confirm
the delete "x" is a small, consistently-sized circle in the corner of each
thumbnail (not overlapping most of it), and the two buttons below line up the
same way at every width.

=====================================================================
6) Tagalog / English toggle — removed (excluded for now)
=====================================================================
Removed:
- The EN/TL buttons from the public navbar (resources/views/layouts/public.blade.php)
- The `/lang/{locale}` route (routes/public.php)
- The SetLocale middleware registration (bootstrap/app.php)

Left in place but now unused/inert, safe to ignore or delete later if you want:
- app/Http/Middleware/SetLocale.php (the class itself — no longer registered
  anywhere, so it never runs)
- lang/tl.json (the Tagalog strings — no longer reachable since nothing sets
  the locale anymore; all pages render in English)
Neither of those two files is included in this zip since removing them isn't
necessary for the feature to be fully inactive — only add/overwrite files were
needed here, not deletions.

=====================================================================
7) Agency seeder removed from the seed run
=====================================================================
database/seeders/DatabaseSeeder.php no longer calls AgencySeeder — only
IncidentTypeSeeder, PersonnelRoleSeeder, and AdministratorSeeder run. Agencies
are created dynamically by the admin from inside the app, so a fixed/sample
agency seed isn't needed and risked colliding with whatever agencies you've
already created for real.

The AgencySeeder.php class file itself is untouched and still exists in your
project (harmless, unused) — not included in this zip since it doesn't need
any changes, only its DatabaseSeeder call needed removing.

=====================================================================
Files changed (everything in this zip)
=====================================================================
app/Http/Controllers/Admin/IncidentController.php
bootstrap/app.php
database/seeders/DatabaseSeeder.php
public/css/public.css
public/js/gps-camera.js
resources/views/admin/incidents/show.blade.php
resources/views/agency/incidents/show.blade.php  (includes the earlier @json/view:clear fix from last round, in case it wasn't applied yet)
resources/views/components/gps-camera.blade.php
resources/views/layouts/app.blade.php
resources/views/layouts/public.blade.php
routes/public.php

=====================================================================
Not touched / please note
=====================================================================
- No migrations, no seeding was run, nothing here touches your existing
  database data.
- resources/views/personnel/incidents/show.blade.php was NOT changed — it
  already uses the same shared `<x-gps-camera />` component and the same
  layouts/app.blade.php button-loading script, so items 2–4 above fix it too
  automatically, with no separate personnel-specific file needed.
- If your live server's version of any of these 11 files has other manual
  edits made directly on the server since your last export, diff before
  overwriting so you don't lose those.
