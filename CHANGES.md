# RANIAG — Changes in This Update

This package contains **only the files that were added or modified** — it
mirrors your project's folder structure, so you can extract it directly into
your existing `raniag/` project and it will overwrite the changed files
without touching anything else (your `vendor/`, `node_modules/`, `.git`,
`.env`, database, etc. are all untouched).

---

## 0. How to apply this update

1. **Back up first** (or make sure you can `git diff` afterward) — just in case.
2. Extract this zip so that `raniag/` merges into your current project root
   (i.e. `app/`, `public/`, `resources/`, `routes/` here overwrite the same
   paths in your project).
3. Delete one now-unused file that this update replaces:
   ```
   public/images/letterhead/bayan-logo.jpg
   ```
   (it's replaced by `public/images/letterhead/bayan-logo.png`, referenced
   by the updated letterhead partial — the old `.jpg` is safe to delete).
4. Clear compiled view/config cache so Laravel picks up the Blade changes:
   ```bash
   php artisan view:clear
   php artisan config:clear
   php artisan route:clear
   ```
5. No `npm install` / `composer install` needed — nothing here changes a
   dependency list, it only adds a self-hosted, pre-built copy of the
   Bootstrap Icons font under `public/vendor/bootstrap-icons/` (see §4
   below), which is plain static assets, not an npm package.

That's it — reload the app and check the items below.

---

## 1. Official logos replaced

**Files:** `public/images/letterhead/bayan-logo.png` (new),
`public/images/letterhead/mdrrmo-logo.png` (replaced),
`resources/views/admin/reports/partials/_letterhead.blade.php`

- Swapped in the two official seal images you provided.
- The old `bayan-logo.jpg` is no longer referenced (delete it — see step 3
  above); the letterhead partial now points at `bayan-logo.png`.

**Check it:** Generate any PDF report (Admin → Reports → Generate, or open
any incident → Generate Official Report). The letterhead at the top should
show your new logos.

---

## 2. Case Documents thumbnail spacing fixed

**Files:** `resources/views/admin/incidents/show.blade.php`, `public/css/public.css`

- The old markup wrapped each 70×70 thumbnail in an oversized
  `position-relative` div, leaving big uneven gaps whenever a document was
  attached. Replaced with a compact `.rg-docthumb-row` flex grid with a
  consistent small gap, no matter how many documents are on file.

**Check it:** Admin → open any incident with attached documents → **Case
Documents Repository** section.

---

## 3. Incident map icons centralized (one source of truth)

**Files:** `public/js/incident-map-icons.js` (new), `public/css/public.css`,
`resources/views/dashboard.blade.php`,
`resources/views/admin/incidents/show.blade.php`,
`resources/views/agency/incidents/show.blade.php`,
`resources/views/personnel/incidents/show.blade.php`

- Previously, four different pages each had their **own separate** copy of
  the marker-icon logic (different CSS classes, different fallback rules),
  which is why pins could look inconsistent from one map to another.
- Now every map calls the same `window.RaniagIcons.buildDivIcon()` function
  and uses one shared `.raniag-marker-pin` CSS class, so a given incident
  type's pin (icon + color) looks identical on the dashboard's Situational
  Map and on every incident detail page's map.

**Check it:** Compare a pin for the same incident on the Dashboard
Situational Map vs. that incident's own detail page map — they should now
match exactly.

---

## 4. Icons are fully self-hosted — no more leaving the system

**Files:** `public/vendor/bootstrap-icons/` (new, self-hosted font +
CSS), `resources/views/layouts/app.blade.php`,
`resources/views/layouts/public.blade.php`,
`resources/views/auth/login.blade.php`,
`resources/views/auth/forgot-password.blade.php`,
`resources/views/auth/reset-password.blade.php`,
`resources/views/errors/404.blade.php`,
`resources/views/admin/incident_types/index.blade.php`,
`app/Http/Controllers/Admin/IncidentTypeController.php`,
`app/Support/IconLibrary.php`

- Every page previously loaded Bootstrap Icons from an external CDN
  (`cdn.jsdelivr.net`). It's now bundled locally under
  `public/vendor/bootstrap-icons/` and every layout points there instead —
  the system no longer depends on that external site being reachable to
  even render its own icons.
- Removed the **"browse the full icon set ↗"** link (which sent admins to
  `icons.getbootstrap.com`) from the Incident Type icon picker. Picking an
  icon is now 100% in-app: a searchable grid of the whole built-in catalog,
  grouped by category (Fire & Hazards, Water & Weather, Medical, etc.).
- Added a **"Reset to Default"** button in the Incident Type edit modal. It
  only appears for the original built-in types (Fire, Flood, Crime,
  Medical, Traffic, Disaster, Infrastructure, Other) and restores that
  type's original seeded icon + color in one click.

**Check it:** Admin → Incident Types → Edit any built-in type. Try
searching the icon grid, and click "Reset to Default" after changing the
icon/color.

---

## 5. Notification bell position/overlap bug fixed

**File:** `resources/views/components/notification-bell.blade.php`,
`resources/views/layouts/app.blade.php`

- Root cause: the top navbar wasn't pinned (`position: sticky`), so its
  on-screen position varied page to page as content scrolled — but the
  mobile notification panel was hardcoded to open at a fixed `top: 56px`,
  which no longer lined up, causing the overlap/"jumping" you screenshotted.
- Fixed by making the navbar `position: sticky; top: 0` (same position on
  every page, always visible), and rebuilding the mobile notification panel
  as a true full-screen overlay (`inset: 0`) instead of guessing an offset.
- Also fixed the cramped "No notifications yet" empty state (more breathing
  room, bigger icon).

**Check it:** Open the bell on several different pages (some with long
content, some short) on both desktop and a narrow/mobile-width browser
window — it should now open in the same place every time and never overlap
page content.

---

## 6. "Move to Bin" — delete notifications

**Files:** `routes/web.php`, `app/Http/Controllers/NotificationController.php`,
`resources/views/notifications/index.blade.php`,
`resources/views/components/notification-bell.blade.php`

- **Full Notifications page:** checkboxes on every notification, a **"Move
  to Bin"** button (deletes only what you've checked) and a **"Delete
  All"** button, plus a trash icon on each row for one-off deletes.
- **Bell dropdown:** a trash icon next to "Mark all read" clears everything,
  and each notification row in the dropdown now has its own small trash
  icon.
- New routes: `DELETE /notifications/{id}`,
  `DELETE /notifications/bulk/delete`, `DELETE /notifications/bulk/delete-all`
  — all scoped to the logged-in user, so no one can delete another user's
  notifications.

**Check it:** Notifications page (`/notifications`) — select a few, click
"Move to Bin"; also try "Delete All". Then check the bell dropdown's trash
icons too.

---

## 7. GPS camera — flash/torch button

**Files:** `public/js/gps-camera.js`,
`resources/views/public/report/create.blade.php`

- Added a flash toggle button next to the camera-switch button. It only
  appears when the **rear ("environment") camera** is active **and** the
  device/browser reports it actually supports a torch — otherwise it stays
  hidden rather than showing a control that would silently do nothing.
- Useful for incidents reported at night or in dark locations.

**Check it:** Public incident report form → open the GPS camera → make
sure you're on the rear camera → look for the flash/lightning icon (only
shows on supported phone hardware, e.g. most Android phones with a rear
flash — not all iPhones/browsers expose torch control to the web).

---

## 8. Case document scanning — real in-app camera (no more OS camera app)

**Files:** `public/js/document-camera.js` (new),
`resources/views/admin/incidents/show.blade.php`

- Previously, "Take Photo" for case documents just triggered
  `<input capture="environment">`, which hands off to your phone's native
  camera app — poorly framed or poorly lit shots were the main reason the
  automatic text scan (OCR) came out inaccurate.
- Now "Take Photo" opens an **in-system guided camera** (same idea as the
  GPS camera): a live preview with a framing guide overlay, on-screen
  instructions ("lay the document flat, fill the frame, hold steady"), a
  flash toggle, and a camera-switch button. After capturing, you can retake
  or confirm — confirming feeds that photo straight into the existing text
  scan/review flow.
- "Upload File" is unchanged — still a normal file picker for existing
  photos or PDFs.

**Check it:** Admin → open an incident → Case Documents Repository → any
document type → **Take Photo**. You should see the live guided camera, not
your phone's regular camera app.

---

## 9. PDF report design made consistent

**File:** `resources/views/admin/reports/pdf.blade.php`

- The multi-incident list report (`pdf.blade.php`) was still using an older
  green/Arial design left over from before the official single-incident
  report (`single_pdf.blade.php`) was redesigned in navy/Helvetica.
  Reskinned it to match: same navy (`#1a365d`) theme, same typography,
  same section-title/table styling.
- Also fixed the report table's HTML indentation, which was causing the
  Priority/Status badge columns to render misaligned, and gave every column
  an explicit width so the table lines up cleanly.

**Check it:** Admin → Reports → Generate a filtered incident-list PDF, and
compare it against a single official incident report — they should now
look like they belong to the same system.

---

## Files in this package

```
app/Http/Controllers/Admin/IncidentTypeController.php
app/Http/Controllers/NotificationController.php
app/Support/IconLibrary.php
public/css/public.css
public/images/letterhead/bayan-logo.png              (new)
public/images/letterhead/mdrrmo-logo.png
public/js/document-camera.js                          (new)
public/js/gps-camera.js
public/js/incident-map-icons.js                        (new)
public/vendor/bootstrap-icons/**                       (new)
resources/views/admin/incident_types/index.blade.php
resources/views/admin/incidents/show.blade.php
resources/views/admin/reports/partials/_letterhead.blade.php
resources/views/admin/reports/pdf.blade.php
resources/views/agency/incidents/show.blade.php
resources/views/auth/forgot-password.blade.php
resources/views/auth/login.blade.php
resources/views/auth/reset-password.blade.php
resources/views/components/notification-bell.blade.php
resources/views/dashboard.blade.php
resources/views/errors/404.blade.php
resources/views/layouts/app.blade.php
resources/views/layouts/public.blade.php
resources/views/notifications/index.blade.php
resources/views/personnel/incidents/show.blade.php
resources/views/public/report/create.blade.php
routes/web.php
```

**Delete manually after extracting:** `public/images/letterhead/bayan-logo.jpg`
