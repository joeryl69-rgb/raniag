# RANIAG — Round 5: announcement UX, login layout, email redesign

## 1. Announcement modal — no longer developer-facing
- Replaced the raw **"Badge (optional)"** / **"Icon (bi-\*)"** text inputs
  with a single plain-language **Type** dropdown: General Update, New
  Feature, Maintenance/Downtime Notice, Safety Alert, Reminder. Each
  option sets the right badge label and icon automatically behind the
  scenes — the MDRRMO officer never sees or types an icon class name.
- Reordered the form to Title → Message → Type → Published, which is the
  natural fill order.
- Editing an existing announcement re-selects the closest matching Type
  automatically; if an older entry doesn't exactly match a preset, its
  original badge/icon is preserved rather than silently overwritten.

## 2. Login page layout (mobile)
- The brand panel (RANIAG / MDRRMO Pamplona / tagline / bullet points) now
  renders **after** the form on small screens (CSS `order`, not DOM
  order — screen readers still meet the form last, sighted layout shows
  form first), so the sign-in fields are immediately visible instead of
  being pushed below a tall block.
- Panel is also more compact on mobile: smaller mark/title, tighter
  spacing, so even where it does show below the form it doesn't dominate
  the page.
- Added a **"Back to Landing Page"** link at the top of the form side on
  every auth page (login, forgot-password, reset-password).

## 3. Consistent loading indicator on every auth form
- Login already had its own spinner-on-submit; forgot-password and
  reset-password did not, so submitting felt like nothing was happening.
- Added one shared script in `auth-split.blade.php` that automatically
  disables the submit button and shows a spinner for **any** auth form,
  system-wide — no per-page script needed. Login's existing custom
  spinner is untouched (the shared script detects it and skips
  duplicating it).

## 4. Email redesign — branded shell, no exposed raw link
- Built a shared `<x-mail-shell>` component (gradient header with your
  logo + org name, consistent footer) and applied it to all three system
  emails: password reset, feedback replies, and approved-document
  notices. They now look like one consistent product instead of three
  different plain templates.
- **Password reset was Laravel's stock default email** — plain text, and
  it always appended a "having trouble clicking the button, copy this URL"
  line that exposed the raw reset link (the thing you flagged). Replaced
  it entirely with a custom `App\Mail\ResetPasswordMail` + branded view:
  button only, no raw link text, clear "expires in 60 minutes" line.
  Wired via `User::sendPasswordResetNotification()`.

## 5. One thing to fix yourself (not shipped in the zip)
`.env` has a broken sender name — open it and find:
```
MAIL_FROM_NAME="${RANIAG}"
```
change it to:
```
MAIL_FROM_NAME="RANIAG"
```
That literal `${RANIAG}` (visible as the sender name in your Gmail
screenshot) is why it wasn't rendering — `.env` files don't expand nested
variables like that. This file is user/environment-specific, so it's
intentionally not included in the patch zip; it's one line to edit by hand.

---

## What to do next
1. Extract the zip into your project root (9 files).
2. Fix `.env` per item 5 above.
3. `php artisan config:clear && php artisan view:clear && php artisan cache:clear`
4. Send yourself a password reset email and confirm: no raw URL text,
   correct sender name, matches the new branded look.
5. Check the login page on your phone — form should appear first, brand
   block compact below it, with a working Back-to-Landing link.
6. Try creating an announcement with the new Type dropdown.
