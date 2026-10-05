# RANIAG — Round 56 Changes

The public site now carries an official alert posture, a hotline list,
and an advisories page. Home and Track Report drop the dark panels,
mascot, and radar animation so the reporting actions stay readable.

=====================================================================
1. Public alert posture and hotlines
=====================================================================
Every public page shows the municipal seal, the MDRRMO seal, Pamplona,
the date, and a posture chip: Normal, Monitoring, Blue Alert, or Red
Alert. An optional short note appears on the home page and on
Advisories.

Administrators set the posture and the call list under Public alert
and hotlines. A line marked Live is a tap-to-call number on the home
page and on Advisories. A hidden line stays off the public site.

Files:
  database/migrations/2026_10_05_154700_add_public_desk_tables.php
  app/Models/SystemSetting.php
  app/Models/PublicHotline.php
  app/Support/AlertPosture.php
  app/Http/Controllers/Admin/PublicDeskController.php
  app/Providers/AppServiceProvider.php
  resources/views/admin/public_desk/index.blade.php
  resources/views/components/sidebar-nav.blade.php
  routes/admin.php

=====================================================================
2. Advisories page
=====================================================================
Advisories lists published updates, with search, and shows the open
evacuation-center count beside the hotlines. Draft announcements stay
hidden. Home links to the full list.

Files:
  app/Http/Controllers/Public/AdvisoryController.php
  app/Http/Controllers/Public/HomeController.php
  resources/views/public/advisories/index.blade.php
  routes/public.php

=====================================================================
3. Quieter public pages
=====================================================================
Home is a light panel: report and track are the actions, the landmark
photo is a full frame, and the four steps no longer sit on a dark
animated rail. Track Report is a light lookup card. The placeholder
is RAN-AB12CD, matching issued numbers (RAN- plus six letters or
digits). The live map panel uses a map icon. The community dashboard
uses the same blue as the rest of the site. Staff login shows both
seals.

Files:
  resources/views/layouts/public.blade.php
  resources/views/public/home.blade.php
  resources/views/public/track/index.blade.php
  resources/views/public/hazard/map.blade.php
  resources/views/public/dashboard.blade.php
  resources/views/components/auth-split.blade.php
  public/css/public.css
  public/css/auth.css

=====================================================================
4. Tests
=====================================================================
Files:
  tests/Feature/Public/PublicDeskTest.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
Run the new migration on the live database.

- Public home: seals, Pamplona, and a Normal chip are in the header.
  Report an Incident and Track a Report are on a light panel.
- Staff sidebar: Public alert and hotlines. Set Blue Alert, add a
  note, and add a Live hotline. The home page and Advisories show
  them. Uncheck Live and the number disappears.
- Advisories: a published announcement is listed. A hidden one is not.
- Track Report: the lookup card is light, and the box shows RAN-AB12CD.
- Community dashboard: the open card and period switch are blue.
- Staff login: both seals appear above RANIAG.
