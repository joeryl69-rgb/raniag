# RANIAG — Round 57 Changes

Push alerts can sound and vibrate, the command center cards no longer
overlap, and an agency cannot request the same incident's printable
documents twice after approval. A pending request can be cancelled.

=====================================================================
1. Push notifications
=====================================================================
The Appearance control is the Push notifications switch again. When
it is on, Alert options offers Sound and Vibrate, both on by default.
A phone alert uses a short double pulse. Sound is the device's normal
notification sound. A computer plays the sound only.

The status line says "Push notifications are on." Send test
notification still sends a live alert to the saved devices.

Files:
  resources/views/settings/appearance.blade.php
  public/js/push-notifications.js
  public/sw.js
  app/Services/WebPushService.php
  app/Http/Controllers/PushSubscriptionController.php
  app/Models/PushSubscription.php
  routes/web.php
  database/migrations/2026_10_05_182100_add_alert_options_to_push_subscriptions.php

=====================================================================
2. Command center cards
=====================================================================
Signal Health stays inside its column. It no longer covers the
Month, Quarter, and Year controls or the Reports, last 6 months chart.

Files:
  resources/views/dashboard.blade.php

=====================================================================
3. Agency document requests
=====================================================================
After a request is approved, sent, or the PDF has already been
generated, that incident is left out of Single Request and Bulk
Request, and the incident page will not accept another request.
A rejected request can still be resubmitted.

A pending row has Cancel request. Cancelling releases the incident
so the agency can submit a new single or bulk request. An approved
request cannot be cancelled, and an administrator cannot approve or
reject a request that is no longer pending.

Files:
  app/Services/DocumentRequestService.php
  app/Services/NotificationService.php
  app/Http/Controllers/Agency/DocumentRequestController.php
  app/Http/Controllers/Admin/PrintableReportRequestController.php
  resources/views/agency/document_requests/index.blade.php
  resources/views/agency/incidents/show.blade.php
  routes/agency.php
  tests/Feature/Agency/DocumentRequestGuardTest.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Appearance: the switch reads Push notifications. With it on, Alert
  options shows Sound and Vibrate checked. Send test notification
  reaches the phone.
- Command Center: Signal Health sits above Reports, last 6 months,
  and the period buttons are on their own row.
- Agency document requests: an approved tracking number is not in
  Single Request or Bulk Request. A pending row has Cancel request.
  After cancel, that incident can be requested again.
