# RANIAG — Upgrade Roadmap (Revised)
### From incident tracker to full disaster-response platform

This revises the original roadmap after a pass through the actual source (not just the architecture doc). The shape is mostly the same — phases ordered by dependency, not excitement — but Phase 0 and Phase 1 now carry the things that would otherwise cause the system to fail *in the actual emergency it exists for*, and a few real bugs are called out explicitly so they don't get lost in a "polish" bucket.

---

## Phase 0 — Foundation (make it trustworthy before making it bigger)

**Goal:** nothing visible breaks under real load or exposes anyone's data. Every later phase assumes this is done.

### Carried over from the original plan
| Item | Why it can't wait |
|---|---|
| `QUEUE_CONNECTION` from `sync` to `database` + `queue:work` under Supervisor | SMS/email/push currently block the HTTP request. |
| Move evidence/document storage off local disk to S3 | No redundancy, doesn't survive migration, doesn't scale past one instance. |
| 2FA for administrator/agency accounts | Staff hold decrypted access to reporter PII. |
| Real-time layer (Laravel Reverb) replacing dashboard polling | New incidents appear 15–30s late on the command center. |
| SLA breach → automatic escalation job | `sla_target_hours` is currently a passive display metric only. |
| Test coverage for status transitions, assignment/ack, resolution, notification fan-out | Current tests are almost entirely Breeze auth scaffolding. |
| Verify production `.env`; confirm `/debug-session` unreachable | Quick to check, bad to miss. |
| Data retention / deletion policy (RA 10173) | Soft-deletes exist, no documented retention/erasure policy. |

### New — found while reading the actual code
| Item | Why |
|---|---|
| **Make evidence storage private**, served through an authorized controller or signed URLs | `EvidenceService` writes to the `public` disk. Every crime/medical/domestic-incident photo is world-readable at a permanent guessable URL with no auth check today. This is the single highest-priority fix in the whole review. |
| **Gate public tracking-number lookups** (a PIN issued at submission, or last-4 of reporter phone) or drop tracking numbers from the public dashboard feed | `/track` shows full description + street address to anyone holding the number, and the public dashboard's recent-activity feed exposes those numbers. Together that's a real deanonymization path. |
| **Fix `resolved_at`/`closed_at`**: they're columns on `incidents` but never written anywhere — every duration metric routes through `assignments` instead, which is why the analytics logic is more contorted than it needs to be | Bug |
| **Move the state-machine guard into `IncidentService::recordStatusChange()`** itself | `canTransitionTo()` is currently only checked in the Agency/Personnel controllers, and `ResolutionService` pushes `Submitted → Assigned`, which isn't a legal transition in `IncidentStatus`'s own enum — the guard needs to live where the writes happen, not be opt-in per caller. |
| **Fix the `??` precedence bug** in `ResolutionService::submitResolution()` (`'...'.$resolvedBy->agency?->name ?? 'Agency'` — string concat binds tighter than `??`, so the fallback never fires) | Bug |
| **Fix the SLA-compliance formula** — `min(100, round($target/$avg*100))` is a ratio of target to average, not "% of incidents resolved within target." Also switch the measured window from `assigned_at → completed_at` to `reported_at → resolved_at` (once that column is actually written) so it reflects dispatch delay, not just field time | Bug — the Command Center ring is currently showing a number that isn't what its label says it is |
| **Cache the admin dashboard `/dashboard.json` payload** (~20–30s) | It currently runs ~25 uncached aggregate queries including `TIMESTAMPDIFF` (MySQL-only, breaks portability to the SQLite test DB) and an unbounded `recent_incidents` query, polled every 4s per open admin tab |
| **Exclude authenticated routes from the service-worker cache** (`/notifications/poll`, `/profile`, etc.) — currently only `/admin`, `/agency`, `/dashboard`, and a handful of auth paths are excluded | Origin-scoped SW cache can hold authenticated response bodies from routes not on the exclusion list |
| **Ship a real `.env.example`** and wire up `routes/console.php` (there's currently no scheduler at all — no SLA sweep, no failed-SMS retry, no retention purge, no backup job) | `composer run setup` copies from `.env.example`, which doesn't exist in the repo; a fresh clone fails on step two |
| **Stop holding a DB transaction open across external I/O** in `submitAnonymousReport()` — reverse-geocoding (Nominatim) and tile-fetch/watermarking (both 5s each, per photo) currently run *inside* the same transaction that creates the incident row | Under a multi-photo report this can hold a row lock for tens of seconds; move evidence processing to a queued job (this also becomes trivial once Phase 0's queue fix lands) and let the reporter get their tracking number immediately |

**Output of this phase:** the system doesn't get more fragile — or leakier — as you add features on top of it.

---

## Phase 1 — Public reporter: ease of access *and* actually reachable in an emergency

**Goal:** reporting an emergency should be possible under stress, not just faster. The original Phase 1 was mostly UX; this version adds the access gaps that currently mean some reporters flatly *can't* file at all.

### Carried over
- Step-wizard report flow (type → location → evidence → contact) with visible progress
- Voice-to-text for the description field
- Live tracking page upgrade — show assigned unit status/position, not just a text timeline
- QR posters per barangay pre-filling the barangay field

### New / promoted — the actual access blockers
| Item | Why |
|---|---|
| **Make evidence optional, not required.** Keep the trust tiering you already built (GPS-verified photo → auto-trusted, `priority=1`), but let a photo-less report through into a call-back/verification queue instead of rejecting the submission outright | `StoreIncidentReportRequest` currently requires `evidence` and `meta.gps_captures`. Someone fleeing, a witness at night, a denied camera permission, or a low-end phone means the report literally cannot be filed today. This is a bigger real-world gap than any UI change. |
| **Offline-tolerant submission via IndexedDB outbox + Background Sync**, with an idempotency key so a reconnect-triggered retry can't create a duplicate case | `sw.js` currently only handles `GET` requests — a dropped connection mid-barangay during a typhoon silently loses the report today. This absorbs the "offline-tolerant submission" line from the original plan and makes it concrete. |
| **Duplicate/cluster detection at submission time** — same incident type within ~150m and ~30 min gets linked as a corroborating report on an existing case, not a new parallel one | Turns the existing "redundancy tracker" chart from retrospective into a live triage signal, and stops one fire from becoming twenty untriaged rows |
| **Inbound SMS reporting + call-taker quick-entry form** | PhilSMS is currently outbound-only. Most real emergency reports to a municipal office arrive by phone call or SMS, not a web form — this is a missing intake channel, not a nice-to-have. |
| **Language toggle (Filipino / Ilocano / Itawis / English)** | The codebase ships `lang/tl.json` only; Ilocano and Itawis are what's actually spoken in Pamplona barangays |

---

## Phase 2 — Responders (agency/personnel): ease of access

*(Unchanged from original — still correctly sequenced after Phase 1.)*

- One-tap mobile status updates (accept → en route → on scene → resolved) — deliberate mobile-first redesign of just the responder flow
- Turn-by-turn navigation link to the incident's GPS pin
- Structured per-incident-type checklists replacing free-text-only resolution
- Offline-tolerant field updates (same outbox mechanism as Phase 1)
- Two-way SMS thread

---

## Phase 3 — Dispatcher power tools

*(Unchanged, plus one addition.)*

- Dispatcher console: priority + SLA-risk sorted queue, one-click nearest-agency assignment
- Resource/unit availability tracking
- Broadcast alert tool (push + SMS + web-push to a barangay or hazard zone)
- After-action report auto-generation (extends existing `PrintableReportService`)
- Training/drill mode
- **New:** responder ETA and live position on the dispatcher's map — this is what makes the console worth building rather than a filtered table with buttons

---

## Phase 4 — Hazard Zone & Evacuation Center Management

*(Unchanged — this is your own idea, and it's still correctly scoped and sequenced.)*

- `hazard_zone_types`, `hazard_zones`, `evacuation_centers` tables, following the existing `incident_types`/`agencies` conventions
- Leaflet.draw for admin polygon drawing
- `GeofenceService::activeHazardZonesContaining()` — auto-flag reports inside an active hazard zone, auto-escalate priority / auto-notify agency
- Live public map (active zones colored by severity + evacuation center markers with capacity)
- Nearest-open-evacuation-center surfaced on the report confirmation and tracking pages
- Evacuee/shelter registry with vulnerable-individual flagging (PWD, elderly, pregnant)
- PAGASA advisory integration to auto-suggest/auto-create hazard zones

---

## Phase 5 — Community & reach *(new phase — pulled out of "later bets" because these are cheap and high-value, not speculative)*

- **Reporter reply channel** — `pending_info` status exists but the reporter currently has no way to respond; let them add a photo/update from the tracking page
- **Barangay relay accounts** — captains filing on residents' behalf matches how reports actually move in a rural LGU better than assuming universal self-reporting
- **"What to do now" guidance** per incident type, shown on the tracking page and hazard-zone view
- **Monthly auto-generated MDRRMO summary** for DILG/OCD reporting, reusing the existing PDF/XLSX pipeline

---

## Phase 6 — Longer-horizon ideas (worth having, not worth building yet)

*(This is the original Phase 5, renumbered — nothing here has changed.)*

- Native mobile app (React Native/Flutter) + Mapbox — revisit once there's a dedicated mobile budget; Blade+Alpine+Leaflet/OSM remains the right call for now
- Multi-tenant / multi-LGU support — `config/raniag.php` currently bakes in one municipality's boundary/barangays/SLA target; generalizing is a real architectural rework, worth doing after Phases 1–4 prove the feature set
- Predictive hotspot analytics — needs a meaningful volume of historical incident+hazard data first
- Integration with national emergency systems — an institutional/partnership question as much as a technical one

---

## Suggested sequencing

```
Phase 0 (foundation + the security/bug fixes above)
        │
        ▼
Phase 1 (public — including the access-blocker fixes) ──┬──▶ Phase 3 (dispatcher)
Phase 2 (responder)                                       ┘
        │
        ▼
Phase 4 (hazard zones)
        │
        ▼
Phase 5 (community/reach)
        │
        ▼
Phase 6 (later bets)
```

Phase 0 is still non-negotiable first — and now includes fixing two things (private evidence storage, tracking-number exposure) that would be genuinely bad to ship even in a small pilot. Within Phase 1, the access-blocker items (optional evidence, offline outbox) are worth pulling *ahead* of the UX items (step-wizard, QR posters) if you're building in dependency-then-impact order rather than strictly top-to-bottom, since they're the difference between someone being able to file a report at all versus filing it more comfortably.
