RANIAG changelog — round 19 (Subresource Integrity on CDN tags)

HOW TO APPLY
Extract this zip's `resources/` folder directly on top of your existing
project folder — 5 changed files, same paths, nothing else touched, no DB
impact, no artisan cache-clear required (Blade views aren't config-cached).

=====================================================================
Files changed
=====================================================================
- resources/views/dashboard.blade.php
    Added integrity/crossorigin to the Leaflet CSS and JS tags (hash
    already in use consistently elsewhere in the codebase for the same
    version — reused, not guessed).
- resources/views/layouts/public.blade.php
    Added integrity/crossorigin to the Bootstrap bundle JS tag (same
    reuse rationale as above).
- resources/views/layouts/app.blade.php
    Added integrity/crossorigin to the Alpine.js 3.14.8 CDN tag. Hash
    computed locally (sha384) from the exact file published in the
    alpinejs@3.14.8 npm package, which is byte-identical to what
    jsdelivr serves for npm-scoped packages.
- resources/views/admin/feedback/index.blade.php
    Added integrity/crossorigin to the Quill 1.3.7 JS tag (quill.min.js).
    Hash computed the same way, from the quill@1.3.7 npm package.
- resources/views/admin/incidents/show.blade.php
    Added integrity/crossorigin to the tesseract.js 4.1.1 JS tag. Hash
    computed the same way, from the tesseract.js@4.1.1 npm package.

=====================================================================
Deliberately NOT changed, and why
=====================================================================
- chart.umd.min.js (used in resources/views/dashboard.blade.php and
  resources/views/public/dashboard.blade.php)
- quill.snow.min.css (used in resources/views/admin/feedback/index.blade.php)

Chart.js has not shipped a stable, officially prebuilt minified CDN file
since v3.9.1 — this is confirmed by an open feature request on the
Chart.js GitHub repo (chartjs/Chart.js#11455) and by a separate real-world
case where a guessed/stale integrity hash for chart.umd.min.js broke a
production site until the hash was removed. Quill's npm package likewise
only ships the unminified quill.snow.css, not the minified CDN build.
Guessing a hash for either risks a hash mismatch that silently blocks the
script/stylesheet from loading — worse than the current "no SRI" state.

Two safe follow-up options for these two, when you're ready:
  1. Self-host them via npm + Vite (you already have both in the build
     pipeline) instead of pulling from a CDN — removes the SRI problem
     entirely since there's no cross-origin fetch to verify.
  2. Switch specifically these two tags to a CDN that publishes an
     official, stable hash for the exact same minified file (verify
     directly on the CDN's own page before using it — don't copy a hash
     from a blog post or Stack Overflow answer).

=====================================================================
Verify after deploy
=====================================================================
Open each affected page in a browser with DevTools open (Network + Console
tabs): dashboard, public dashboard, admin feedback (rich text editor),
admin incident detail (map + OCR upload). Confirm no
"Failed to find a valid digest" / SRI errors in the console, and that
Leaflet maps, Alpine-driven UI, the Quill editor, and OCR all still work.
Then re-run the Mozilla HTTP Observatory scan — Subresource Integrity
should move from Failed toward Passed once chart.js/quill.snow.css are
addressed via one of the two options above; this round alone won't flip
that line item fully green since two CDN tags are intentionally still
unhashed.
