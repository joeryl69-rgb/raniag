RANIAG changelog — round 17 (Generate Reports revamp: AOR scope + Chart
Summary; GPS camera capture button color; resolution review acknowledgment;
re-dispatch button gating)

HOW TO APPLY
Extract this zip's `raniag/` folder directly on top of your existing project
folder — 9 changed files, same paths, nothing else touched, no DB impact.
Then: php artisan route:clear && php artisan view:clear && php artisan optimize:clear

=====================================================================
1) Generate Reports — the revamp (previously scoped out in round 16)
=====================================================================
What changed on admin/reports/generate (Generate Reports page):

- AOR SCOPE is now an explicit 3-way choice instead of a single "Include
  Outside-AOR" checkbox:
    - MDRRMO Pamplona AOR Only (default — same as before)
    - Outside-AOR Only (referred to another jurisdiction)
    - All (both, clearly labeled in the output)
  This is a real, named distinction now, not a bolt-on checkbox, and it's
  applied the exact same way across PDF, Excel, AND the new Chart Summary
  report (one shared filter/query method in ReportController).

- NEW: "Generate Chart Summary" — a third report type alongside PDF/Excel.
  Produces a PDF with:
    - Report View: Periodic (single total for the whole range, same as
      today's PDF/Excel), Weekly (grouped by calendar week), or Monthly
      (grouped by calendar month) — controls the Trend chart's buckets and
      the period-over-period comparison sentence in the summary.
    - Charts to Include: the admin checks any combination of Status
      Breakdown, Incident Type Breakdown, Barangay Hotspots (top 10), and
      Trend Over Time. Only the checked charts render, in that order,
      every time.
    - A written summary at the top (total incidents, top incident type,
      top barangay hotspot, and — when Weekly/Monthly is selected — a
      first-half-vs-second-half volume comparison and the busiest period).

  On consistency: every number in the Chart Summary — the bars, the
  percentages, the summary sentences — comes straight out of COUNT()/
  GROUP BY aggregation and fixed arithmetic (share of total, % change
  between two known sums). None of it is free-text-generated, so the same
  filters always produce byte-identical output. Charts are drawn as plain
  CSS width-percentage bars (no chart image library — dompdf can't run JS),
  which also means they render identically on every generation.

Check: on Generate Reports, pick a date range with data, leave all four
chart checkboxes checked, set Report View to Weekly, and click "Generate
Chart Summary" — you should get a PDF with a written summary followed by
up to 4 bar-chart sections. Re-run it with only "Barangay Hotspots" checked
— only that one section should appear. Switch AOR Scope to "Outside-AOR
Only" and confirm the incidents shown are the referred ones, labeled as
such.

Files: app/Http/Controllers/Admin/ReportController.php,
resources/views/admin/reports/index.blade.php,
resources/views/admin/reports/chart_summary_pdf.blade.php (new),
routes/admin.php

=====================================================================
2) GPS Camera — Capture Photo button no longer reads as "active" while
   still waiting on a GPS/address fix
=====================================================================
Root cause: the button was always btn-success (green), just dimmed via
opacity while disabled. The dimming wasn't a strong enough signal on top
of a live camera background — it still read as a normal, clickable green
button during "Waiting for GPS signal…".

Fix: the button now starts neutral (btn-outline-light, disabled) and only
switches to green (btn-success) once a location has actually resolved
(barangay or municipality matched) and the evidence cap hasn't been hit.
This is a genuine color swap, driven by the same updateCaptureReadiness()
gate that already controlled the disabled state — not a new readiness
rule, just a clearer visual for the existing one. Shared component, so
this applies everywhere GPS Camera is used (public report form, agency
and personnel resolution forms).

Check: open GPS Camera on an agency/personnel resolution form — Capture
Photo should look neutral/gray while "Waiting for GPS signal…" is showing,
then turn green once the coordinates and address resolve.

Files: resources/views/components/gps-camera.blade.php, public/js/gps-camera.js

=====================================================================
3) Confirm Resolution modal — "Confirm & Submit" no longer green by
   default
=====================================================================
Same underlying issue as #2, different screen: the modal's "Confirm &
Submit" button was green and enabled the instant the modal opened, before
the agency/personnel had actually looked at what they were about to send.

Fix: added a required "I've reviewed the above and confirm it's accurate"
checkbox. "Confirm & Submit" now starts neutral (btn-outline-secondary)
and disabled, and only turns green + enabled once that box is checked.
The checkbox and button both reset (unchecked/disabled) every time the
modal is opened, so re-opening after "Go Back & Edit" — or a second
resolution later in the same page session — never inherits a stale
"already reviewed" state. Applied identically to both
agency/incidents/show.blade.php and personnel/incidents/show.blade.php.

Check: fill out a resolution form, click "Resolve Incident" — the review
modal's "Confirm & Submit" should be gray and disabled until you check the
new acknowledgment box, at which point it turns green and becomes
clickable.

Files: resources/views/agency/incidents/show.blade.php,
resources/views/personnel/incidents/show.blade.php

=====================================================================
4) Re-dispatch — "Confirm Re-dispatch" now requires an actual selection
=====================================================================
Root cause: the button had no client-side gating at all — it was always
enabled/blue regardless of whether any agency or personnel checkbox was
checked, and the panel didn't reset itself when collapsed, so a stale
selection could sit there from a previous open.

Fix: "Confirm Re-dispatch" now starts disabled and only becomes clickable
once at least one agency/personnel checkbox is checked. Collapsing the
re-dispatch panel closed now properly resets the form (all checkboxes
unchecked, notes cleared, button disabled again) instead of leaving
whatever was selected last time.

Check: on an in-progress incident's admin page, expand "Re-dispatch / Add
Another Agency" — "Confirm Re-dispatch" should be grayed out. Check one
agency/personnel box — the button should become enabled. Collapse the
panel and re-expand it — everything should be back to unchecked/disabled.

Files: resources/views/admin/incidents/show.blade.php

=====================================================================
Files changed (everything in this zip)
=====================================================================
app/Http/Controllers/Admin/ReportController.php
public/js/gps-camera.js
resources/views/admin/incidents/show.blade.php
resources/views/admin/reports/chart_summary_pdf.blade.php  (new file)
resources/views/admin/reports/index.blade.php
resources/views/agency/incidents/show.blade.php
resources/views/components/gps-camera.blade.php
resources/views/personnel/incidents/show.blade.php
routes/admin.php

No migrations, no seeding — nothing here touches your database.
