# RANIAG — Full-Stack Reference

> LGU Pamplona (MDRRMO) Incident Reporting & Dispatch System.
> This document is a complete snapshot of the **current, existing** stack, architecture, database schema, routes, and conventions — generated from the actual project source so it can be dropped into Cursor as project context. It is documentation of what already exists, not a spec for something new.

---

## 1. What the system does

RANIAG lets the public report incidents (fire, flood, crime, medical, traffic, disaster, infrastructure, other) with GPS location + photo/video/document evidence, tracks each report through a status pipeline, assigns it to a responding **agency** (and optionally a specific **personnel** member), lets that agency/personnel submit a **resolution**, and gives admins a command-center dashboard, reporting/export tools, and printable/official document generation. It also runs a public community dashboard, a support/feedback ticket system, announcements, SMS + email + web-push notifications, and a PWA with offline support.

Three account roles: **administrator**, **agency**, **personnel**. Public users need no account (anonymous reporting + tracking-number lookup).

---

## 2. Tech stack

| Layer | Technology |
|---|---|
| Backend framework | **Laravel 12** (PHP ^8.2) |
| Auth scaffold | Laravel Breeze (Blade stack) |
| Templating | **Blade** views (no SPA framework — no Vue/React/Inertia in use) |
| Frontend interactivity | **Alpine.js 3** + vanilla JS modules in `public/js/` |
| CSS | **Bootstrap 5.3** (CDN) is the actual UI framework in the Blade layouts; Tailwind + `@tailwindcss/vite` are present in `package.json`/`vite.config.js` but the shipped layouts render with Bootstrap. Custom theme CSS lives in `resources/css/` and `public/css/` |
| Build tool | **Vite 7** (`laravel-vite-plugin`) |
| Maps | **Leaflet 1.9.4** (incident map, geofencing, dashboards) |
| Charts | **Chart.js** (`chart.umd.min.js`, admin + public dashboards) |
| Rich text | **Quill 1.3.7** (admin feedback replies) |
| OCR | **Tesseract.js 4.1.1** (extracting text from uploaded incident documents in-browser) |
| PDF generation | **barryvdh/laravel-dompdf ^3.1** |
| Excel export | Custom **`SimpleXlsxWriter`** service (no external package) |
| Web Push | **minishlink/web-push ^11.0** (VAPID keys), custom service worker `public/sw.js` |
| SMS | **PhilSMS** (only supported provider; Twilio/TextBee/Semaphore removed) |
| Email | SMTP (Gmail in dev), Laravel `Mail` |
| Database | **MySQL** in production (`DB_CONNECTION=mysql`); SQLite used for local/tests (`database/database.sqlite`) |
| Sessions/Cache/Queue | `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync` (jobs run inline unless changed) |
| Testing | **Pest 3** (`pestphp/pest`, `pestphp/pest-plugin-laravel`) |
| Code style | **Laravel Pint** |
| Local dev orchestration | `composer run dev` → `php artisan serve` + `queue:listen` + `pail` (log viewer) + `vite` concurrently |
| Deployment | Docker (`richarvey/nginx-php-fpm` + Nginx + PHP-FPM) **or** Nixpacks (`nixpacks.toml`, Railway-style) — both configs exist in the repo |
| PWA | `public/manifest.json` + `public/sw.js` service worker, offline fallback page, installable app |

---

## 3. Directory structure (actual)

```
raniag/
├── app/
│   ├── Console/Commands/GenerateVapidKeys.php
│   ├── Enums/                     # PHP 8.1+ backed enums (see §6)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # administrator-only controllers
│   │   │   ├── Agency/            # agency-role controllers
│   │   │   ├── Personnel/         # personnel-role controllers
│   │   │   ├── Public/            # unauthenticated public controllers
│   │   │   ├── Auth/              # Breeze auth controllers
│   │   │   ├── NotificationController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── AppearanceSettingController.php   # per-user theme/dark-mode/font
│   │   │   └── PushSubscriptionController.php
│   │   ├── Middleware/
│   │   │   ├── EnsureUserHasRole.php     # alias: role
│   │   │   ├── EnsureUserIsActive.php    # alias: active
│   │   │   ├── PreventBackHistoryCache.php # alias: no-cache
│   │   │   └── SecurityHeaders.php       # appended globally
│   │   └── Requests/               # FormRequest validation, grouped by area
│   ├── Jobs/                       # DispatchSmsJob, DispatchWebPushJob
│   ├── Mail/                       # Mailables (see §8)
│   ├── Models/                     # Eloquent models (see §5)
│   ├── Notifications/ResetPasswordNotification.php
│   ├── Policies/                   # AssignmentPolicy, IncidentPolicy
│   ├── Providers/AppServiceProvider.php
│   ├── Repositories/               # IncidentRepositoryInterface + IncidentRepository
│   ├── Services/                   # business logic layer (see §8)
│   ├── Support/                    # Filters, IconLibrary, ThemePresets
│   └── View/Components/            # AppLayout, GuestLayout
├── bootstrap/app.php                # middleware aliases, proxy trust, exceptions
├── config/raniag.php                # app-specific config (see §4)
├── database/
│   ├── migrations/                  # ~40 migrations (see §5 for resulting schema)
│   ├── seeders/
│   └── database.sqlite              # local/test DB
├── public/
│   ├── js/                          # vanilla JS: gps-camera, document-camera,
│   │                                 # filter-bar, live-refresh, push-notifications,
│   │                                 # password-strength, public-report, map icons
│   ├── sw.js                        # PWA service worker
│   ├── manifest.json
│   └── build/                       # Vite output
├── resources/
│   ├── css/app.css
│   ├── js/app.js, bootstrap.js
│   └── views/
│       ├── admin/                   # agencies, announcements, audit-logs,
│       │                            # document_requests, feedback, incident_documents,
│       │                            # incident_types, incidents, personnel,
│       │                            # personnel_roles, reports, sms-logs
│       ├── agency/                  # archived_reports, document_requests, incidents, support
│       ├── personnel/incidents
│       ├── public/                  # report, track, support, home, dashboard
│       ├── auth/, profile/, settings/, notifications/, emails/
│       ├── components/ + components/filters/ + components/public/
│       └── layouts/                 # app.blade.php, public.blade.php, guest.blade.php, navigation.blade.php
├── routes/
│   ├── web.php      # entry point, requires all others below
│   ├── public.php   # public/anonymous routes
│   ├── admin.php    # administrator routes
│   ├── agency.php   # agency routes
│   ├── personnel.php# personnel routes
│   └── auth.php     # Breeze auth routes
├── tests/{Unit,Feature}/  + Pest.php
├── Dockerfile, conf.d/laravel.conf, scripts/00-laravel-deploy.sh
├── nixpacks.toml
├── vite.config.js, tailwind.config.js
└── CHANGES-round*.md   # dev changelog notes for each work round
```

---

## 4. App-specific configuration — `config/raniag.php`

Central config file for domain constants:

- `name`, `organization` (MDRRMO Pamplona), `tagline`
- `sla_target_hours` (default 48) — drives the "SLA Compliance" ring on the admin Command Center
- `tracking.prefix` = `RAN`, `tracking.segment_length` = 4 → tracking numbers like `RAN-XXXX-XXXX`
- `roles`: `administrator` / `agency` / `personnel`
- `map`: default center (Pamplona, Cagayan ≈ 18.472, 121.325), default zoom 13
- `address`: fallback municipality/province/country used in reverse-geocoding + photo watermarks
- `pamplona_boundary_path`: GeoJSON polygon of municipal boundary, used by `GeofenceService` to flag reports outside the LGU's jurisdiction
- `pamplona_barangays_path`: GeoJSON of the 18 official barangay boundaries (PSGC 0201518000), used for point-in-polygon barangay resolution
- `evidence`: max 5 files, 5120 KB max size, allowed mimes `jpg,jpeg,png,gif,webp,pdf,mp4,mov,webm`
- `geolocation`: high-accuracy GPS, 15s timeout
- `gps_camera`: JPEG quality 0.88, max 5 captures
- `barangays`: the 18 official barangay names (Abanqueruan, Allasitan, Bagu, Balingit, Bidduang, Cabaggan, Capalalian, Casitan, Centro, Curva, Gattu, Masi, Nagattatan, Nagtupacan, San Juan, Santa Cruz, Tabba, Tupanna)

---

## 5. Database schema (current, from migrations)

### `users`
Base Laravel columns (`name`, `email`, `password`, etc.) **plus** RANIAG additions:
`role` (string, default `agency`), `agency_id` (FK→agencies, nullable), `phone`, `role_title`, `team_assignment`, `is_active` (bool), `avatar_path`, `theme_key`, `dark_mode`, `follow_system`, `font_key`, `font_size` (all nullable — per-user appearance, falls back to app defaults).

### `agencies`
`name`, `code` (unique), `description`, `email`, `phone`, `address`, `is_active`, timestamps, **soft deletes**.

### `incident_types`
`name`, `slug` (unique), `description`, `icon`, `color`, `is_active`, `sort_order`, `default_icon`, `default_color`, `default_priority` (per-type default used when a public report is submitted with that type).

### `incidents` (core entity)
`tracking_number` (unique), `incident_type_id` (FK, restrict delete), `agency_id` (FK, nullable, null on delete), `status` (default `submitted`), `priority` (default `medium`), `title`, `description`, `location_address`, `barangay`, `latitude`/`longitude` (decimal), `reporter_name`/`reporter_email`/`reporter_phone` (email/phone widened to TEXT to fit Laravel's `encrypted` cast ciphertext), `is_anonymous`, `reported_at` (DATETIME, not auto-updating — fixed after a MySQL quirk bug), `resolved_at`, `closed_at`, `meta` (json), timestamps, **soft deletes**. Indexed on `(status, reported_at)` and `agency_id`.

### `evidence`
`incident_id` (FK, cascade), `uploaded_by` (FK→users, nullable), `type` (photo/video/document/audio), `file_path`, `original_filename`, `mime_type`, `file_size`, `caption`, `priority` (tinyint, 0=normal/1=high), `is_gps_capture` (bool). Indexed `(incident_id, priority, is_gps_capture)`.

### `incident_documents`
Official case-file documents distinct from citizen-submitted evidence. `incident_id` (FK, cascade), `uploaded_by`, `document_type` (string: `call_taker_form` / `dispatch_form` / `narrative_report` / `endorsement_sheet`), `file_path`, `original_filename`, `mime_type`, `file_size`, `is_camera_capture` (bool), `notes`, `extracted_text` (longText — OCR output from Tesseract.js). Indexed `(incident_id, document_type)`.

### `assignments`
`incident_id` (FK, cascade), `agency_id` (FK, nullable, restrict), `assigned_by` (FK→users, restrict), `assigned_to` (FK→users, nullable), `notes`, `is_active` (bool), `assigned_at`, `completed_at`, `acknowledged_at`, `acknowledged_by` (FK→users, nullable). Indexed `(incident_id, is_active)`.

### `status_updates`
Timeline/audit trail for status transitions. `incident_id` (FK, cascade), `user_id` (FK, nullable), `from_status`, `to_status`, `comment`, `is_public` (bool — controls whether it shows on the public tracking page). Indexed `(incident_id, created_at)`.

### `resolutions`
`incident_id` (FK, cascade), `resolved_by` (FK→users, restrict), `summary`, `actions_taken`, `resolved_at`. Indexed `incident_id`.

### `document_requests`
Agency/personnel requests for printable official reports. `incident_id` (FK, cascade), `requesting_agency_id` (FK→agencies, cascade), `requested_by` (FK→users), `request_type` (`single`, future `bulk`), `status` (`pending`/`approved`/`sent`/`failed`), `admin_comment`, `generated_path`, `generated_at`, `sent_at`, `failed_reason`, `request_note`, `requested_sections` (json — which content blocks to include in the PDF), `archived_at`.

### `notifications` (custom table — not Laravel's built-in notifications table)
`user_id` (FK, cascade), `incident_id` (FK, cascade nullable), `type`, `title`, `message`, `data` (json), `channel` (default `database`), `read_at`. Indexed `(user_id, read_at)`.

### `sms_logs`
`incident_id` (FK, nullable), `user_id` (FK, nullable), `recipient_phone`, `message`, `status` (`pending`/`sent`/`failed`), `provider`, `provider_message_id`, `provider_response` (json), `sent_at`, `failed_at`. Indexed `(status, created_at)`.

### `activity_logs`
Generic audit log. `user_id` (FK, nullable), `log_name`, `description`, polymorphic `subject` (`nullableMorphs`), `event`, `properties` (json), `ip_address`, `user_agent`. Indexed `(log_name, created_at)`.

### `feedback_submissions`
Public + staff "Support Center" tickets. `category` (feedback/concern/suggestion/bug), `subject`, `message`, `submitter_name`, `submitter_email`, `status` (new/reviewed/resolved), `admin_notes`, `reviewed_by`, `reviewed_at`, `ticket_no` (unique, format `RG-yymmdd-XXXXX`), `submitted_via` (`public`/`agency`), `agency_id`, `submitted_by`, `admin_reply` (longText), `replied_by`, `replied_at`.

### `announcements`
`title`, `badge` (e.g. "New Feature"), `body`, `icon`, `is_published`, `published_at`, `created_by`. Indexed `(is_published, published_at)`. Shown on the public landing page.

### `personnel_roles`
`title` (unique), `is_active`, `sort_order`. Admin-manageable list of personnel job titles (replaces old hardcoded arrays). `users.role_title` stays a plain string column intentionally — not a FK — so retiring a role never breaks existing accounts.

### `system_settings`
Single-row legacy global settings (theme_key, dark_mode, follow_system, font_key, font_size, updated_by). **Superseded** by per-user appearance columns on `users` (see below) but table/columns retained.

### `push_subscriptions`
Web Push subscriptions, one per browser/device. `user_id` (FK, cascade), `endpoint` (500 chars), `public_key`, `auth_token`, `content_encoding`, `user_agent`. Unique `(user_id, endpoint)`.

### Standard Laravel tables
`password_reset_tokens`, `sessions` (DB-backed), `cache`/`cache_locks`, `jobs`/`job_batches`/`failed_jobs`.

---

## 6. Enums (`app/Enums/`)

| Enum | Cases |
|---|---|
| `UserRole` | `administrator`, `agency`, `personnel` |
| `IncidentStatus` | `submitted → received → assigned → in_progress → (pending_info) → resolved → closed`, plus terminal `rejected`, `outside_aor`. Each case exposes `availableTransitions()` enforcing the valid state machine. |
| `IncidentPriority` | `low`, `medium`, `high`, `critical` |
| `EvidenceType` | `photo`, `video`, `document`, `audio` |
| `IncidentDocumentType` | `call_taker_form`, `dispatch_form`, `narrative_report`, `endorsement_sheet` |
| `NotificationChannel` | `database`, `mail`, `sms` |
| `SmsLogStatus` | `pending`, `sent`, `failed` |

**Status state machine** (`IncidentStatus::availableTransitions()`):
```
submitted    → received, rejected, outside_aor
received     → assigned
assigned     → in_progress, rejected
in_progress  → pending_info, resolved
pending_info → in_progress, resolved
resolved     → closed
closed / rejected / outside_aor → (terminal)
```

---

## 7. Models & relationships (`app/Models/`)

- **User** (`Authenticatable`) — belongsTo `Agency`; hasMany `Assignment` (as assigner/assignee via `assignmentsMade`/`assignmentsReceived`), `StatusUpdate`, `Resolution` (as resolver), `SmsLog`, `ActivityLog`. Helpers: `isAdministrator()`, `isAgency()`, `isPersonnel()`, `homeRoute()` (redirects to the right dashboard by role), `appearance()` (resolves per-user theme with fallback to `ThemePresets`), `avatar_url`/`initials` accessors.
- **Agency** — hasMany `User`, `Incident`, `Assignment`.
- **IncidentType** — hasMany `Incident`.
- **Incident** — belongsTo `IncidentType`, `Agency`; hasMany `Evidence`, `IncidentDocument`, `Assignment` (`currentAssignments`, `activeAssignment` scoped to active), `StatusUpdate` (ordered timeline), `Resolution`, `SmsLog`, `DocumentRequest`; morphMany `ActivityLog`. Computed: `status_timeline`/`public_timeline` accessors, `documentAvailability()`, `missingRequiredDocumentTypes()`.
- **Evidence** — belongsTo `Incident`, `User` (as `uploader`).
- **IncidentDocument** — belongsTo `Incident`, `User` (as `uploader`).
- **Assignment** — belongsTo `Incident`, `Agency`, `User` (as `assigner`/`assignee`/`acknowledger`). `isAcknowledged()` helper.
- **StatusUpdate** — belongsTo `Incident`, `User`.
- **Resolution** — belongsTo `Incident`, `User` (as `resolver`).
- **DocumentRequest** — belongsTo `Incident`, `Agency` (as `requestingAgency`), `User` (as `requestedByUser`).
- **Notification** — belongsTo `User`, `Incident`. `scopeUnread`, `isRead()`, `icon()`/`color()`/`url()` presentation helpers.
- **SmsLog** — belongsTo `Incident`, `User`.
- **ActivityLog** — belongsTo `User`; morphTo `subject`.
- **FeedbackSubmission** — belongsTo `User` (as `replier`/`reviewer`/`submitter`), `Agency`. `categoryLabel()`, `categoryIcon()`, `isFromAgency()`.
- **Announcement** — belongsTo `User` (as `author`). `scopePublished`.
- **PersonnelRole** — standalone lookup table.
- **SystemSetting** — belongsTo `User` (as `updatedBy`). Singleton-style "current settings" pattern.
- **PushSubscription** — belongsTo `User`.

---

## 8. Service layer (`app/Services/`) — business logic

| Service | Responsibility |
|---|---|
| `IncidentService` | Core incident lifecycle: creation, status transitions, validation rules tied to `IncidentStatus::availableTransitions()` |
| `AssignmentService` | Assigning incidents to agencies/personnel, acknowledgment, completion |
| `ResolutionService` | Recording/updating resolutions |
| `EvidenceService` | Uploading, storing, and managing evidence files (largest service, 572 lines) |
| `IncidentDocumentService` | Managing official case documents (call taker form, dispatch form, etc.) |
| `DocumentRequestService` | Agency/personnel requests for printable reports |
| `PrintableReportService` | Generating the printable/official incident report (feeds dompdf) |
| `IncidentArchiveZipService` | Bundling an incident's files into a ZIP archive |
| `NotificationService` | Fan-out to database/mail/SMS/web-push channels (largest logic file, 440 lines) |
| `WebPushService` | Sending VAPID-signed Web Push notifications via `minishlink/web-push` |
| `GeofenceService` | Point-in-polygon checks against the Pamplona municipal boundary + barangay resolution from GeoJSON |
| `TrackingNumberService` | Generates unique `RAN-XXXX-XXXX`-style tracking numbers |
| `ActivityLogService` | Writes to `activity_logs` for audit trail |
| `SimpleXlsxWriter` | Hand-rolled `.xlsx` writer used for report exports (no external Excel package) |

**Repository pattern**: `IncidentRepositoryInterface` + `IncidentRepository` abstract incident querying/filtering away from controllers.

**Policies**: `IncidentPolicy`, `AssignmentPolicy` — Laravel authorization for incident/assignment actions.

**Jobs (queued)**: `DispatchSmsJob`, `DispatchWebPushJob` — async dispatch (run synchronously by default since `QUEUE_CONNECTION=sync`).

**Mail** (`app/Mail/`): `DocumentRequestApprovedMail`, `FeedbackReplyMail`, `IncidentStatusUpdateMail`, `ResetPasswordMail`.

**Support** (`app/Support/`): `Filters` (query filter helpers), `IconLibrary` (Bootstrap Icons catalog for pickers), `ThemePresets` (named color/theme presets + defaults for the appearance system).

---

## 9. Routing map

All routes registered from `routes/web.php`, which `require`s the others.

### Public (`routes/public.php`, prefix `public.*`, no auth)
- `GET /` → landing page (`Public\HomeController`)
- `GET /community-dashboard` + `/community-dashboard/data.json` → public live dashboard
- `GET/POST /support`, `/feedback` → Support Center ticket form (throttled 5/min)
- `GET/POST /report` → public incident report form (throttled 10/min); `GET /report/{trackingNumber}/success`
- `GET/POST /track` → tracking-number lookup (throttled 20/min)
- `GET /offline` → PWA offline fallback

### Admin (`routes/admin.php`, prefix `/admin`, middleware `auth, verified, active, role:administrator, no-cache`)
- Dashboard: `/dashboard`, `/dashboard.json`, `/dashboard/boundary.json`, `/dashboard/barangays.json`
- `/sms-logs`, `/audit-logs`
- `feedback.*` (index/update/reply), `announcements.*` (full CRUD + toggle)
- `incident_documents.index`, `incident_types.*` (CRUD + toggle)
- `incidents.*`: index, show, validate, reply, assignments, resolutions.update, documents.store/destroy/update_text
- `assignments.*`: store, update, complete
- `agencies` — full resource route (`Route::resource`)
- `personnel.*`: edit/update/destroy; `personnel_roles.*`: full CRUD + toggle
- `document_requests.*`: index, approve, reject
- `reports.*`: generate (PDF), generate_excel, generate_chart_summary

### Agency (`routes/agency.php`, prefix `/agency`, middleware `role:agency`)
- Dashboard (same 4-route pattern as admin)
- `document_requests.*` (index, bulk_store, archive/unarchive), `incidents.print_requests.store`
- `incidents.*`: index, show, update_status, accept (assignment)
- `incidents.resolution` (store/update)
- `archived_reports.*`: read-only history of resolved/closed incidents

### Personnel (`routes/personnel.php`, prefix `/personnel`, middleware `role:personnel`)
- Same dashboard pattern; `incidents.*` (index/show/update_status/accept); print request store; resolution store/update

### Shared authenticated (`routes/web.php`, middleware `auth, active, no-cache`)
- `/dashboard` → redirects to role-specific dashboard via `User::homeRoute()`
- `agency.support.*` — Support Center for **signed-in staff** (agency + personnel), deliberately at `/support-center` not `/support` to avoid colliding with the public route
- `/profile` (edit/update/destroy)
- `notifications.*`: index, poll, mark_read, mark_all_read, destroy, destroy_selected, destroy_all
- `push_subscriptions.*`: store/destroy
- `settings.appearance.*`: index/update/reset — per-user theme/dark-mode/font, available to every role
- `/debug-session` — **local environment only**, guarded by `app()->environment('local')`

### Auth (`routes/auth.php`, Breeze)
Login, forgot/reset password, email verification, password confirmation, logout. **Public self-registration is disabled** — accounts are admin-provisioned only.

---

## 10. Middleware

| Alias | Class | Purpose |
|---|---|---|
| `role:{role}` | `EnsureUserHasRole` | Restricts a route group to one of `administrator`/`agency`/`personnel` |
| `active` | `EnsureUserIsActive` | Blocks login/access for deactivated (`is_active=false`) accounts |
| `no-cache` | `PreventBackHistoryCache` | Prevents browser back-button from showing cached authenticated pages after logout |
| (global append) | `SecurityHeaders` | Adds security headers to every response |

`bootstrap/app.php` also: trusts all proxies (`trustProxies(at: '*')` — needed behind Railway/Render/Hostinger load balancers), and excludes the `download_token` cookie from encryption (read by frontend JS via `document.cookie` to detect when a file download has started).

---

## 11. Frontend conventions

- **No JS framework/SPA** — server-rendered Blade + Alpine.js for interactivity + vanilla JS modules per feature.
- Key `public/js/` modules: `gps-camera.js` (GPS-tagged photo capture + watermarking for public reports), `document-camera.js` (staff document capture), `filter-bar.js`, `live-refresh.js` (polling-based live dashboard updates), `push-notifications.js` (Web Push subscribe/unsubscribe), `password-strength.js`, `public-report.js`, `incident-map-icons.js`.
- Maps via Leaflet, loaded from CDN with SRI hashes.
- Charts via Chart.js (CDN, intentionally left without SRI — see round-19 changelog, no stable minified CDN build exists).
- Rich text editing (admin feedback replies) via Quill, loaded from CDN with SRI.
- In-browser OCR on uploaded incident documents via Tesseract.js, loaded from CDN with SRI.
- **Theming**: CSS custom-property based, driven by `ThemePresets` + per-user `theme_key`/`dark_mode`/`follow_system`/`font_key`/`font_size` columns on `users`. `data-theme="dark|light"` set on `<html>` server-side, with a pre-paint inline `<script>` override when "follow system" is enabled, to avoid flash-of-wrong-theme.
- PWA: `public/manifest.json` + `public/sw.js` (cache-first for a small app-shell asset list, `Promise.allSettled` install so one bad CDN fetch can't break the whole service worker), `/offline` fallback page.

---

## 12. Notification pipeline

Three channels, fanned out by `NotificationService`:
1. **Database** (`notifications` table) — in-app bell/notification center (`NotificationController`), polled via `/notifications/poll`.
2. **Mail** — `IncidentStatusUpdateMail`, `DocumentRequestApprovedMail`, `FeedbackReplyMail`, `ResetPasswordMail`.
3. **SMS** — via `DispatchSmsJob` → PhilSMS API, logged to `sms_logs`.
4. **Web Push** — via `DispatchWebPushJob` → `WebPushService` (VAPID keys, `minishlink/web-push`), subscriptions tracked per-device in `push_subscriptions`.

---

## 13. Reporting & documents

- **Admin → Reports**: generate PDF (dompdf) and Excel (`SimpleXlsxWriter`) reports, plus chart-summary data for dashboards.
- **Document Requests**: agencies/personnel request an official printable packet for an incident (`requested_sections` json picks which blocks to include — incident details, evidence photos, call taker form, dispatch form, narrative report, endorsement sheet); admin approves/rejects; approved requests generate a PDF via `PrintableReportService` and can be archived by the agency once handled.
- **Incident Documents**: distinct from evidence — official forms (`call_taker_form`, `dispatch_form`, `narrative_report`, `endorsement_sheet`) uploaded/captured by staff, OCR'd client-side with Tesseract.js, text stored in `extracted_text`.

---

## 14. Environment variables in use (names only — values are per-deployment secrets)

```
APP_NAME=RANIAG, APP_ENV, APP_KEY, APP_DEBUG, APP_TIMEZONE=Asia/Manila, APP_URL, APP_LOCALE
DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
SESSION_DRIVER=database, CACHE_STORE=database, QUEUE_CONNECTION=sync
MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_DEFAULT_REGION / AWS_BUCKET  (present but S3 disk not the default; FILESYSTEM_DISK=local)
SMS_PROVIDER=PhilSMS, PHILSMS_API_TOKEN, PHILSMS_SENDER_ID
VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT
RANIAG_NAME, RANIAG_ORGANIZATION, RANIAG_TAGLINE, RANIAG_SLA_TARGET_HOURS, RANIAG_TRACKING_PREFIX
RANIAG_MAP_LAT, RANIAG_MAP_LNG, RANIAG_MAP_ZOOM
RANIAG_MUNICIPALITY, RANIAG_PROVINCE, RANIAG_COUNTRY
RANIAG_PAMPLONA_BOUNDARY_PATH, RANIAG_PAMPLONA_BARANGAYS_PATH
RANIAG_EVIDENCE_MAX_FILES, RANIAG_EVIDENCE_MAX_SIZE_KB
RANIAG_GEO_TIMEOUT_MS, RANIAG_GEO_MAX_AGE_MS, RANIAG_GPS_MAX_CAPTURES
```
> ⚠️ Your uploaded `.env` contains **real credentials** (DB password, Gmail SMTP password, PhilSMS token, VAPID keys, personal email addresses). Those were intentionally **left out** of this document. Rotate any of these that may have been exposed, and never commit `.env` to a public Cursor project/git remote.

---

## 15. Deployment

Two deployment paths exist in the repo simultaneously:

1. **Docker** — `Dockerfile` (multi-stage: Node 20 builds Vite assets → `richarvey/nginx-php-fpm` runtime), `conf.d/laravel.conf` (Nginx config), `scripts/00-laravel-deploy.sh`. `SKIP_COMPOSER=1`, `WEBROOT=/var/www/html/public`, forces `APP_ENV=production`, `APP_DEBUG=false`, logs to `stderr`.
2. **Nixpacks** — `nixpacks.toml` (php82 + nodejs_20), runs `composer install --no-dev`, `npm ci && npm run build`, caches config/route/view, then `php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT` (Railway-style).

`.github/workflows/` exists for CI (check its contents in the repo for current pipeline specifics — not reproduced here since it's execution config, not architecture).

---

## 16. Local dev commands (from `composer.json`)

```bash
composer install
cp .env.example .env   # if not already present
php artisan key:generate
php artisan migrate --force
npm install && npm run build

# All-in-one dev (server + queue listener + log viewer + vite, concurrently):
composer run dev

# Tests:
composer run test   # clears config cache, then php artisan test (Pest)
```

---

## 17. Notes for continuing this project in Cursor

- This is a **monolithic server-rendered Laravel app** — there is no separate frontend repo/API to keep in sync. Blade views render directly from controllers/services.
- Business logic lives in the **Service layer** (`app/Services/`), not in controllers or models — follow that pattern for new features.
- Status changes must go through `IncidentStatus::availableTransitions()` — don't set `incidents.status` directly without checking the state machine.
- Role-gating is done at the **route group** level (`role:administrator|agency|personnel` middleware), not scattered `if` checks in views — new admin/agency/personnel features should get their own route file section following the existing `admin.php`/`agency.php`/`personnel.php` pattern.
- The `CHANGES-round*.md` files at the project root are a running, dated changelog of past work sessions — worth skimming for "why" context on non-obvious code (e.g. the MySQL timestamp auto-update fix, the SRI-hash rollout, the per-user appearance migration away from global `system_settings`).
- Tailwind is configured in the build but the actual UI is Bootstrap 5 — don't assume Tailwind utility classes will do anything in the existing Blade views without checking `vite.config.js`/`tailwind.config.js` usage first.