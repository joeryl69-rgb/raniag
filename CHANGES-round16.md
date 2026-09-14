RANIAG changelog — round 16 (filter audit, GPS/evidence clarity, docs
repository overlap, resolution review step, password UX, push notif
error message)

HOW TO APPLY
Extract this zip's `raniag/` folder directly on top of your existing project
folder — 15 changed files, same paths, nothing else touched, no DB impact.
Then: php artisan view:clear && php artisan optimize:clear

=====================================================================
1) Auto-filter audit — the real gap list
=====================================================================
Checked every page's filter form against the shared <x-filters.toolbar>
component (the thing that actually provides live search, chips, and
auto-submit-on-change — see resources/views/components/filters/toolbar.blade.php).
Found 5 pages with a filter form that DID NOT use it — meaning they only
filtered after clicking a "Filter"/"Apply" button, not automatically:

- agency/incidents/index.blade.php ("Assigned Incidents Dispatch")
- personnel/incidents/index.blade.php ("Assigned Emergency Responses")
- agency/document_requests/index.blade.php ("My Document Requests")
- agency/archived_reports/index.blade.php ("Resolved Reports")
- admin/incident_documents/index.blade.php ("Case Documents Repository")

All 5 now use the shared toolbar — same live search/chips/auto-submit
behavior as admin/incidents, admin/sms-logs, admin/audit-logs,
admin/feedback, and admin/agencies already had. Every filterable list page
in the app is now consistent.

Note: admin's "Generate Reports" page filters weren't touched here — that
page needs a larger revamp anyway (see item 8 below), so its filters are
being redone as part of that instead of patched twice.

Check: on any of the 5 pages above, change a dropdown or type in search —
results should update without clicking a button, and an active-filter chip
row should appear.

=====================================================================
2) Case Documents Repository — thumbnails still overlapping
=====================================================================
Found a second cause beyond last round's delete-button fix: each thumbnail
also has an OCR "view/edit scanned text" button sitting at bottom-left,
which — combined with the delete button at top-right and thumbnails that
weren't forced to a true square — is what was crowding/merging when a
document type had 2+ files on file.

Fix: thumbnails are now 84px (was 72px) with `!important` on width/height/
object-fit so nothing can stretch them non-square again, and the scan-text
button now mirrors the delete button's treatment (small circular badge,
hanging off the opposite corner) instead of overlapping the photo itself.

Check: upload 2-3 files under one document type (e.g. Dispatch Form) and
confirm each thumbnail is a clean square with two small separate badges
at opposite corners — no overlap between thumbnails or between the two
buttons on one thumbnail.

=====================================================================
3) GPS Camera "green = evidence provided" confusion
=====================================================================
Root cause: the only green indicator near the GPS Camera panel was the
GPS-signal badge, which turns green ("GPS active") the moment a location
lock is found — before any photo has actually been taken. Agencies were
reading that as "I've already provided evidence."

Fix: added a second, clearly separate badge — "No evidence photo yet" in
gray, switching to "N evidence photo(s) attached" in green — that only
turns green once a photo is actually captured or a file is chosen. The
GPS-signal badge still exists and still means what it always meant (GPS
lock status), it's just no longer the only green thing in that panel.

Check: open GPS Camera on an agency/personnel resolution form. The GPS
badge may go green once location locks — the new evidence badge should
stay gray until you actually capture or attach a photo.

=====================================================================
4) Resolution submission — review before you submit
=====================================================================
The "Resolve Incident" button used to submit immediately on click. It now
opens a review modal first, showing exactly what's about to be sent
(Resolution Summary, Actions Taken, evidence file count) plus a note
clarifying this submits *your* agency's/personnel's resolution — if
others are also assigned, they resolve independently and the incident
only closes once everyone assigned has resolved. "Go Back & Edit" closes
the modal with nothing submitted; "Confirm & Submit" actually sends it.
Applied to both agency/incidents/show.blade.php and
personnel/incidents/show.blade.php (identical form in both).

Check: fill out a resolution form and click "Resolve Incident" — a review
modal should appear instead of submitting immediately, showing the exact
text you typed and the evidence count.

=====================================================================
5) Push notification "Registration failed - permission denied"
=====================================================================
Researched this specific error — it's a real, well-documented Chromium/
Edge behavior (NotAllowedError from pushManager.subscribe()), not a bug
in RANIAG's code. It can fire even after the in-page permission prompt
says "granted," because a separate browser-level or Windows-level
notification setting is blocking it underneath.

Fix: the raw browser error text is no longer shown as-is for this specific
case — it's replaced with actual next steps (check the lock-icon site
permissions in Edge, edge://settings/content/notifications, and Windows
Settings → Notifications for the browser).

To actually get push notifications working on the device you screenshotted:
check both of those settings — this is a browser/OS setting, not something
fixable from RANIAG's code.

=====================================================================
6) Password creation UX
=====================================================================
Added a live strength meter + requirement checklist (8+ chars, lowercase,
uppercase, number, symbol — each ticks off in real time) plus a "passwords
match" indicator, to:
- auth/reset-password.blade.php (the real password-CREATION screen — your
  app has public self-registration disabled per routes/auth.php, so this
  reset-password flow, not a /register page, is how every account's
  password actually gets set)
- profile/partials/update-password-form.blade.php (change-password screen)

Both now give the same real-time feedback instead of a bare password field
with no signal until a validation error comes back after submitting.

On "username": checked the whole app for a distinct username field —
there isn't one anywhere; every account uses email as the login identifier
(auth/login.blade.php, autocomplete="username" is on the email field by
convention, not a separate field). If you did want a distinct username
field added as a new feature, that's a real scope decision (touches the
users table, registration/account-creation flow, and login) — let me know
and I'll scope it properly rather than bolt it on here.

=====================================================================
7) NOT done this round — scoped out on purpose
=====================================================================
The "Make Report" admin page revamp (AOR vs. Outside-AOR distinction,
chart/summary export options alongside Excel/PDF, weekly/periodic/monthly
views, letting the admin pick which charts to generate) is a genuinely
large, standalone feature — new UI, new report-generation logic, and a
real design decision about what "consistent, good results" means for
auto-generated chart summaries. Rushing it into the same round as
everything above risks exactly what you said you don't want: inconsistent,
half-baked results. I'd rather scope and build that properly as its own
dedicated round. Let me know when you want to start it and I'll ask what
you need from it before writing anything.

=====================================================================
Files changed (everything in this zip)
=====================================================================
public/css/auth.css
public/css/public.css
public/js/gps-camera.js
public/js/password-strength.js  (new file)
public/js/push-notifications.js
resources/views/admin/incident_documents/index.blade.php
resources/views/agency/archived_reports/index.blade.php
resources/views/agency/document_requests/index.blade.php
resources/views/agency/incidents/index.blade.php
resources/views/agency/incidents/show.blade.php
resources/views/auth/reset-password.blade.php
resources/views/components/gps-camera.blade.php
resources/views/personnel/incidents/index.blade.php
resources/views/personnel/incidents/show.blade.php
resources/views/profile/partials/update-password-form.blade.php

No migrations, no seeding — nothing here touches your database.
