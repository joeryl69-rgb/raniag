# RANIAG — Round 54 Changes

Dashboards, shelter check-in, and report downloads now share the same
month, quarter, and year window. Places where the same incident keeps
coming back are visible, and guests from outside Pamplona stay distinct.

=====================================================================
1. Community dashboard
=====================================================================
Month, Quarter, and Year filter the chart, the incident mix, barangay
totals, and recent activity together. The totals are four cards: Open
now, the selected period, completed cases, and the Pamplona record.
Each period card shows the change against the previous period.

A banner names the most active incident and barangay. Repeat areas
lists barangays where that same incident has come back more than once,
marked Low, Watch, Elevated, or High.

Files:
  app/Http/Controllers/Public/PublicDashboardController.php
  app/Support/PeriodRange.php
  resources/views/public/dashboard.blade.php
  tests/Feature/Public/CommunityDashboardTest.php

=====================================================================
2. Staff command center
=====================================================================
The incident chart uses the same Month, Quarter, and Year switch, with
reports and completed cases. Total incidents and resolved cases show
the percent change. Repeat areas sit beside the chart.

Files:
  app/Http/Controllers/Admin/DashboardController.php
  resources/views/dashboard.blade.php

=====================================================================
3. Evacuation registry
=====================================================================
Check-in can record someone from Pamplona or from another municipality.
Outside guests are badged Outside municipality, and the list can be
filtered to All, Pamplona, or Outside. The hazard cards show change
versus last month, the share of areas shown to the public, the share
of open shelters, and how many people inside came from outside Pamplona.

Files:
  app/Http/Controllers/Admin/HazardEvacController.php
  app/Models/Evacuee.php
  database/migrations/2026_10_02_190000_add_origin_to_evacuees_table.php
  resources/views/admin/hazard/index.blade.php
  resources/views/components/stat-trend.blade.php
  tests/Feature/Admin/OutsideEvacueeTest.php

=====================================================================
4. Reports
=====================================================================
This month, This quarter, and This year fill the dates. Inside
Pamplona, outside Pamplona, or both is always visible. The register,
the spreadsheet, and the decision report include shelter guests with
that distinction, plus the repeat-area table.

Files:
  app/Http/Controllers/Admin/ReportController.php
  resources/views/admin/reports/index.blade.php
  resources/views/admin/reports/pdf.blade.php
  resources/views/admin/reports/decision_pdf.blade.php
  resources/views/admin/reports/partials/_shelters.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Community Dashboard: four cards, not one dark bar. Month, Quarter,
  and Year change the chart title.
- Staff dashboard: the same period switch and a Repeat areas list.
- Hazard map: Check in, choose Outside the municipality, save a guest,
  and see the Outside municipality badge. Filter the registry to Outside.
- Reports: This quarter sets the dates. Outside Pamplona only is in
  the main form. A downloaded file lists shelter guests and repeat areas.
