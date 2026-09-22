RANIAG changelog — round 20 (Sequential Phase 0–5 closeout: Hostinger-aligned)

Date: 2026-09-21

Covers commits on `main` from Phase 0 closeout through Phase 5 community reach
(roadmap rewrite + deployable features). Barangay relay accounts stay cancelled.
Phase 6 (native app / multi-LGU / predictive / national) remains parked.

HOW TO APPLY
Push/pull `main` on Hostinger (GitHub Actions deploy already runs
`git reset --hard origin/main`, `composer install`, `npm run build`,
`php artisan migrate --force`, optimize caches).

After deploy, confirm migrations applied:
  php artisan migrate --force
Optional one-shots:
  php artisan raniag:escalate-sla --dry-run
  php artisan raniag:purge-retained --dry-run
  php artisan raniag:monthly-summary --month=2026-08

Hostinger already runs `schedule:run` every minute — that now drives:
  hourly  → raniag:escalate-sla
  daily   → raniag:purge-retained (02:15)
  monthly → raniag:monthly-summary (1st at 03:00)

=====================================================================
Roadmap
=====================================================================
- RANIAG-Upgrade-Roadmap-Revised.md
    Rewritten as live source of truth with DONE / REMAINING / DEFERRED.
    Barangay accounts marked CANCELLED. Deferred items explicit (S3,
    Reverb, inbound SMS, Ilocano/Itawis, PAGASA auto API, Phase 6).

=====================================================================
Phase 0 — Foundation closeout
=====================================================================
- Email OTP 2FA for administrator + agency after password login
  (personnel skipped). OTP mailed, challenge page, cache-backed hash.
- app/Services/TwoFactorService.php (new)
- app/Mail/LoginOtpMail.php + resources/views/emails/auth/login-otp.blade.php
- app/Http/Controllers/Auth/TwoFactorChallengeController.php
- resources/views/auth/two-factor-challenge.blade.php
- AuthenticatedSessionController + routes/auth.php wired
- config/raniag.php: two_factor + retention settings; .env.example updated
- app/Console/Commands/EscalateSlaBreaches.php → raniag:escalate-sla
- app/Console/Commands/PurgeRetainedData.php → raniag:purge-retained
- routes/console.php: thin Schedule for escalate + purge (+ monthly later)
- tests/Feature/NotificationEscalationTest.php

=====================================================================
Phase 1 — Public reporter
=====================================================================
- Step wizard on report form: type → location → evidence → contact
- Voice-to-text (Web Speech API) with graceful hide if unsupported
- Use-current-location button + barangay QR prefill (?barangay=)
- Admin QR posters print page (admin/qr-posters)
- Cluster / corroborating detection ~150m / ~30 min → meta.cluster +
  redundancy_signal (IncidentService)
- Filipino locale toggle (SetLocale middleware + POST /locale + nav)
- LocaleController, QrPosterController, public-report.js wizard/voice/GPS
- tests/Feature/ClusterDetectionTest.php

=====================================================================
Phase 2 — Responders
=====================================================================
- Migration: assignments.field_phase; incident_types.resolution_checklist;
  sms_logs.direction + thread_note
- Mobile field strip: accepted → en route → on scene + Navigate deep-link
- Shared ResponderFieldController (personnel + agency routes)
- Offline field-phase/SMS outbox: public/js/field-outbox.js
- Staff→reporter SMS with thread note logging (NotificationService)
- Default checklists seeded per incident-type slug; editable in admin
  incident types modal (one item per line)
- components/responder-field-strip.blade.php

=====================================================================
Phase 3 — Dispatcher
=====================================================================
- Migration: agencies.is_available; users.is_available + last_lat/lng/at;
  incidents.is_drill + after_action_pdf_path
- Admin Dispatch Queue UI: SLA risk + priority sort, one-click assign,
  drill toggle, barangay broadcast (in-app notify + SMS)
- Availability toggle routes; agency edit “Available for dispatch”
- After-action PDF auto-generated on full resolve (PrintableReportService)
- Optional personnel location ping every ~2 min on incident show
- DispatchQueueController, AvailabilityController, admin/dispatch/queue

=====================================================================
Phase 4 — Hazard & evacuation
=====================================================================
- Migration: hazard_zone_types, hazard_zones, evacuation_centers, evacuees
- Admin Hazard & Evac UI: GeoJSON zone paste + manual PAGASA note/URL,
  centers, evacuee check-in with vulnerable flag
- Public /hazard-map + nearest open center API
- On report submit: if point in active hazard zone → meta.hazard_zones
  and bump priority (low/medium → high)
- Models: HazardZoneType, HazardZone, EvacuationCenter, Evacuee
- Controllers: Admin\HazardEvacController, Public\HazardMapController

=====================================================================
Phase 5 — Community (no barangay accounts)
=====================================================================
- Migration: incident_types.public_guidance
- Track page: “What to do now” from type guidance; nearest open center;
  session-gated reporter reply when status is pending_info
  (POST /track/{trackingNumber}/reply → resumes in_progress)
- raniag:monthly-summary → PDF + CSV under storage/app/monthly_summaries
- Schedule monthlyOn(1, '03:00')

=====================================================================
Deliberately NOT done (deferred / parked)
=====================================================================
- S3 — private local disk is the Hostinger solution
- Laravel Reverb — keep polling + cache
- Inbound SMS — PhilSMS outbound-only until webhooks exist
- Ilocano / Itawis locale packs — need translators
- PAGASA auto API — manual advisory only for now
- Leaflet.draw polygon editor — GeoJSON paste used instead (same schema)
- Monthly XLSX via PhpSpreadsheet — CSV shipped to avoid new dependency
- Phase 6: native app, multi-LGU, predictive hotspots, national systems
- Live unit GPS on public tracking page as a map layer (ping stored on
  user; public track still does not render live unit markers)

=====================================================================
Verify after deploy
=====================================================================
1. APP_DEBUG=false and APP_ENV=production on Hostinger .env
2. Admin/agency login → email OTP challenge works; personnel login does not
3. Public /report: wizard steps, voice button (Chrome), Filipino toggle,
   /report?barangay=Centro prefills barangay
4. Admin → QR Posters; Dispatch Queue; Hazard & Evac
5. Public /hazard-map loads zones/centers
6. Personnel incident show: field strip + Navigate + offline banner if offline
7. Track a pending_info case with access code → reply form appears and
   advances status
8. php artisan raniag:escalate-sla --dry-run and raniag:monthly-summary --dry-run
   (monthly has no --dry-run; use a past --month=YYYY-MM and check
   storage/app/monthly_summaries)

Commits (main):
  452ae3d  Phase 0 closeout (2FA, SLA, retention)
  a404335  Phase 1 reporter upgrades
  dc77b66  Phase 2 responder field tools
  8675186  Phases 3–5 dispatch / hazard / community
