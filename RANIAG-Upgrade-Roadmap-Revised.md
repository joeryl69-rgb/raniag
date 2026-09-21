# RANIAG — Upgrade Roadmap (Live Status)

LGU Pamplona (MDRRMO) Incident Reporting & Dispatch — Hostinger production track.  
**Roles:** administrator, agency, personnel only. **Barangay relay accounts: CANCELLED.**

Status key: `DONE` | `REMAINING` | `DEFERRED` (hosting / vendor / later)

Last updated: 2026-09-21 (post Phase 0a–1 access deploy)

---

## Phase 0 — Foundation

| Item | Status |
|---|---|
| Private evidence + case docs (local private disk, auth + track session routes) | DONE |
| Tracking PIN / access code + scrub public feed tracking numbers | DONE |
| SW exclusions for authenticated + track routes | DONE |
| State machine in `IncidentService::recordStatusChange()` | DONE |
| `resolved_at` / `closed_at` written on transitions | DONE |
| SLA ring = % within target (`reported_at` → `resolved_at`) | DONE |
| Admin `/dashboard.json` ~25s cache | DONE |
| `.env.example` | DONE |
| Hostinger cron `queue:work --stop-when-empty` | DONE |
| Transaction split (evidence/Nominatim after commit) | DONE |
| Domain Pest (status, assignment, evidence, report/track) | DONE |
| S3 object storage | DEFERRED (private local disk is Hostinger solution) |
| Laravel Reverb realtime | DEFERRED (polling + cache) |
| 2FA for admin/agency (email OTP) | DONE |
| SLA breach escalation (`raniag:escalate-sla`) | DONE |
| Retention / purge (`raniag:purge-retained`) | DONE |
| Notification fan-out + escalation Pest | DONE |
| Laravel Schedule facade | DONE (thin escalate + purge; Hostinger already runs schedule:run) |

---

## Phase 1 — Public reporter

| Item | Status |
|---|---|
| Optional evidence + `needs_verification` queue | DONE |
| Offline outbox + idempotency key | DONE |
| Track evidence after verified lookup | DONE |
| Step-wizard report flow | REMAINING |
| Voice-to-text (description) | REMAINING |
| QR posters / `?barangay=` prefill | REMAINING |
| Duplicate/cluster detection (~150m / ~30 min) | REMAINING |
| Filipino locale toggle (`lang/tl.json`) | REMAINING |
| Inbound SMS reporting | DEFERRED (PhilSMS outbound-only) |
| Ilocano / Itawis packs | DEFERRED (needs translators) |
| Live unit GPS on tracking page | DEFERRED (needs Phase 2/3 telemetry) |

---

## Phase 2 — Responders

| Item | Status |
|---|---|
| Mobile-first status strip (accept → en route → on scene → resolved) | REMAINING |
| Maps navigation deep-link | REMAINING |
| Per-type resolution checklists | REMAINING |
| Offline field updates | REMAINING |
| Outbound SMS thread log (staff ↔ reporter) | REMAINING |

---

## Phase 3 — Dispatcher

| Item | Status |
|---|---|
| Priority + SLA-risk dispatch queue + one-click assign | REMAINING |
| Agency/personnel availability flags | REMAINING |
| Barangay broadcast (push + SMS) | REMAINING |
| After-action PDF auto-generation | REMAINING |
| Drill / training mode flag | REMAINING |
| Optional responder last-known position ping | REMAINING |

---

## Phase 4 — Hazard & evacuation

| Item | Status |
|---|---|
| Hazard zone + evacuation center schema/UI | REMAINING |
| Geofence containment + auto priority flag | REMAINING |
| Public hazard/evac map + nearest center | REMAINING |
| Evacuee registry (vulnerable flags) | REMAINING |
| PAGASA auto API | DEFERRED |
| Manual advisory note on zones | REMAINING |

---

## Phase 5 — Community & reach

| Item | Status |
|---|---|
| Reporter reply on `pending_info` (track session) | REMAINING |
| “What to do now” per incident type | REMAINING |
| Monthly MDRRMO summary PDF/XLSX | REMAINING |
| Barangay relay accounts | CANCELLED |

---

## Phase 6 — Later bets (parked)

Native app, multi-LGU, predictive hotspots, national emergency integrations — not in active track.

---

## Sequencing rule

Complete one phase → Pest → deploy `main` → smoke live → next phase. No skipping ahead.
