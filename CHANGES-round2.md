# Round 2 fixes

8 files changed, mirrors your project structure — unzip and overwrite.

## 1. Icon search now actually dynamic
**File:** `resources/views/admin/incident_types/index.blade.php`
Bug: `filterIcons()` only ever touched the hidden "extra catalog" icons —
the visible quick-pick icons were never filtered, so they looked static
while typing. Now every icon (quick-pick + catalog) is filtered live on
each keystroke, and clearing the box restores the default quick-pick view.

## 2. Color palette wheel — fixed CSS positioning bug
**File:** same file
Bug: the native `<input type="color">` used `class="d-none"`
(`display:none`), which means it has no real on-screen box — so the
browser's native color-picker popup opened floating at the top-left of the
page instead of near the palette swatch. It's now invisible via opacity
instead of display, so it stays part of the layout and the OS picker
anchors correctly to the swatch.

## 3. Missing page title fixed (Incident Types, Feedback & Concerns, Reports)
**Files:** `resources/views/admin/incident_types/index.blade.php`,
`resources/views/admin/feedback/index.blade.php`,
`resources/views/admin/reports/index.blade.php`
These three pages used a different layout pattern (`@extends`/`@section`)
than every other admin page (`<x-app-layout>`), so they were the only
pages with a blank navbar title — confirmed in your Feedback & Concerns
screenshot (top bar shows just the hamburger + bell, no title). Converted
all three to match; also removed the now-duplicate "Feedback & Concerns"
heading that repeated inside the body once the navbar title was restored.

## 4. Bell positioning bug — root cause fixed
**File:** `resources/views/layouts/app.blade.php`
Bootstrap's `.navbar` class defaults to `flex-wrap: wrap`. On mobile, a
long page title (e.g. "Incident Reports Management") pushed the
date+bell group onto its own row — and once alone on that row,
`justify-content: space-between` had nothing to space apart, so it
collapsed to the left, landing the bell **underneath the hamburger
button** instead of staying on the right (this is what you were seeing on
your device). Fixed by forcing the top bar to `nowrap` and truncating the
title instead, so hamburger + bell always stay on one row, right-aligned.

## 5. Select All — bell dropdown + Notifications page
**Files:** `resources/views/components/notification-bell.blade.php`,
`resources/views/notifications/index.blade.php`
- Notifications page: added a "Select all" checkbox above the list.
- Bell dropdown: added a checkbox per notification row + a "Select all" /
  "Move to bin" bar that appears whenever there's something to select.

## 6. Duplicate page title removed
**File:** `resources/views/admin/incident_documents/index.blade.php`
The navbar already says "Case Documents Repository" — the card header
inside the body repeated the exact same title verbatim. Removed the
duplicate heading, kept the description line.

## 7. Case Documents Repository — thumbnail button overlap
**File:** `public/css/public.css`
The per-document remove (×) button sat -6px outside each 72px thumbnail
with only a small gap between thumbnails, so on narrow/mobile widths it
could overlap the neighboring thumbnail's controls. Increased row spacing,
gave the remove button its own stacking layer + a white ring so it's never
visually swallowed by a neighboring thumb, and made "Take Photo" /
"Upload File" stack full-width on very narrow screens instead of
squeezing side-by-side.

## 8. Document scan modal — Retake button, mobile responsiveness, OCR
**Files:** `resources/views/admin/incidents/show.blade.php`,
`public/css/public.css`, `public/js/document-camera.js`
- Added a **Retake** button directly in the "Scan [Form Name]" modal
  itself (previously retake only existed one step earlier, inside the
  live in-app camera, before you ever got to this screen). If the photo
  came from the in-app camera, Retake reopens the guided camera; if it
  was an uploaded file, Retake clears the selection so you can pick a
  different one.
- The modal now goes full-screen on phones (`modal-fullscreen-sm-down`)
  with a **sticky footer**, so "Save Document" is always reachable
  without hunting through a long scrolled form — this is also what was
  making the Save button hard to locate.
- The in-app camera viewfinder itself was boxed into a small fixed
  aspect-ratio rectangle regardless of screen size ("static" sizing).
  It now goes full-screen on phones too, with the live video filling the
  actual available height/width instead of a small fixed box.
- Added lightweight client-side image preprocessing (grayscale +
  contrast boost) before OCR — dim/low-contrast phone photos were a real
  contributor to garbled scan text, not just a code bug. Combined with
  Retake, you can now reshoot a sharper photo if the first scan comes out
  wrong.

---

## What to check
1. **Incident Types** — open Add/Edit modal, type in the icon search box:
   the icon grid should filter live as you type (including the default
   quick-pick icons). Click the palette swatch: the native color picker
   should open right next to it, not top-left of the screen. Confirm the
   page now shows "Incident Types" in the top navbar.
2. **On your phone** — open any admin page with a long title (e.g.
   "Incident Reports Management", "Government Agencies Management"):
   the bell icon should stay on the right of the top bar, not fall below
   the hamburger button.
3. **Feedback & Concerns** and **Reports** pages — should now show a
   page title in the top navbar instead of being blank.
4. **Notifications** — full page: "Select all" checkbox above the list.
   Bell dropdown: open it, you should see a checkbox per item and a
   "Select all" / "Move to bin" bar.
5. **Case Documents Repository** — on an incident, check the document
   thumbnails no longer visually overlap the Take Photo/Upload buttons on
   a narrow screen.
6. **Scan a document** on your phone — Take Photo should open a
   full-screen guided camera. After scanning, you should see a **Retake**
   button in the modal itself, and "Save Document" should always be
   visible/reachable at the bottom without scrolling past it.

## Files in this package
```
public/css/public.css
public/js/document-camera.js
resources/views/admin/feedback/index.blade.php
resources/views/admin/incident_documents/index.blade.php
resources/views/admin/incident_types/index.blade.php
resources/views/admin/incidents/show.blade.php
resources/views/admin/reports/index.blade.php
resources/views/components/notification-bell.blade.php
resources/views/layouts/app.blade.php
resources/views/notifications/index.blade.php
```
