# RANIAG — Round 7: excess filter options, &middot; bug, real-time on the public side + faster interval

33 files. Extract into your project root (overwrite). This supersedes the round 6 zip — it includes everything from round 6 plus the fixes below.

## 1. Feedback category filter had excess/irrelevant options
The admin Feedback & Concerns "Category" filter listed **10** options, but a person can only ever pick **7** on the actual Support Center form (Login Issues, Account, Incident Reports, Technical, Data, Suggestion, Other — see your Image 1). The other 3 ("General Feedback", "Concern", "Bug Report") were legacy category values kept only so old rows still display correctly — they were never meant to be filter choices.
- Added `FeedbackSubmission::filterableCategories()` — the single source of truth for "categories a person can currently choose," used by both the admin filter dropdown and the public/agency intake form (previously the intake form excluded the 3 legacy ones with its own separate hardcoded list — now both use the same method, so they can't drift apart again).
- Audited every other filter dropdown in the app (agencies, audit logs, sms logs, document requests, incident lists, archived reports, incident documents, notifications) — all of them already generate their options dynamically from real enums/DB values, so Feedback was the only one with this problem.

## 2. The `&middot;` bug
Confirmed from your screenshot: `by Chief Inspector Joe &middot; Bureau of Fire Protection` was showing the literal text `&middot;` instead of a bullet character. Cause: the agency-name separator was written as the HTML entity `&middot;` inside a Blade `{{ }}` expression, and `{{ }}` auto-escapes its output for security — so the `&` became `&amp;` and the whole thing printed as text. Fixed by using an actual `·` character (not an entity) in the three incident detail pages (admin/agency/personnel).

## 3. Live updates — extended to the public side, and much faster
Two real gaps, both fixed:
- **Public pages had no live-refresh at all.** The `live-refresh.js` script was only loaded in the staff layout (`layouts/app.blade.php`), never in the public layout — so the landing page's announcements and the public tracking page always needed a manual refresh, exactly as you saw. Now loaded on `layouts/public.blade.php` too, and wired into: the landing page's Updates & Announcements section, and the public tracking page's Progress + Full History (so a reporter watching their tracking page sees status changes without reloading).
- **Interval was too slow.** Default dropped from 15s to **4s** everywhere it's used (13 pages, listed below).
- **Instant-on-return**: added a `visibilitychange`/`focus` listener, so the moment you switch back to a tab (or unlock your phone with the app open), it refreshes immediately instead of waiting out the interval.

**Important honesty note, since you asked for this to be truly instant on any device:** what's shipped here is still *polling* (the browser asks the server every few seconds), just faster and smarter about when to ask. True zero-delay push — the server notifying every open device the instant something happens — requires WebSocket infrastructure (e.g. Laravel Reverb or Pusher) plus a persistent server process and a queue worker. Your `.env` currently has `BROADCAST_CONNECTION=log` and `QUEUE_CONNECTION=sync`, meaning that infrastructure isn't set up, and adding it means running an extra always-on process on your host (not just editing files) — something I don't want to silently bolt on without you confirming your hosting setup can support it (Railway/Render-style hosts usually can, but it's a deploy-config change, not just a code patch). The 4-second poll + instant-on-return should feel close to real-time in practice; if you want to go further, let me know your hosting provider and I can walk through what adding Reverb would take.

Pages now on the 4s live-refresh: Admin/Agency/Personnel incident dispatch queues, Admin/Agency Document Requests, Admin SMS Logs, Admin Audit Logs, Admin Agencies + Personnel Accounts, Admin Case Documents Repository, Admin Announcements, Agency Archived Reports, Admin Feedback & Concerns, **plus (new)** the public landing page's Announcements and the public tracking page's Progress/Full History.

---

## What to do next
1. Extract this zip into your project root (33 files; overwrite the round-6 ones).
2. `php artisan route:clear && php artisan view:clear && php artisan config:clear`
3. Feedback & Concerns → Category filter: should now show exactly 7 options, matching the intake form.
4. Open an incident with a multi-agency history and confirm the agency name shows with a real bullet (·), not `&middot;`.
5. Open the public landing page in one tab and the admin Announcements page in another; publish a new announcement, and watch it appear on the landing page within ~4 seconds without reloading.
6. Open a tracking-number status page in one tab, change that incident's status from the admin side, and watch Progress/Full History update within ~4 seconds.
7. Switch away from a tab with a live page open and back — it should refresh immediately, not wait for the next tick.
