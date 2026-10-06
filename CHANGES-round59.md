# RANIAG Changelog

## 2026-10-06 — Public reporting usability refinement

- Apply the saved public theme before stylesheets load, avoid a mismatched
  navbar theme label during page startup, and synchronize the browser theme
  color with the selected appearance.
- Smooth internal public-page transitions while preserving reduced-motion
  support and normal browser behavior for modified, external, and download
  links.
- Reposition the Community Dashboard report action and JO field guide,
  clarify month-to-date, quarter-to-date, and year-to-date filters, and
  update chart contrast when the theme changes.
- Improve status-step legibility and replace the tracking page's generic
  nearest-evacuation-center alert with a responsive live-map callout.
- Improve contrast for the floating help action and report wizard controls,
  including the mobile dark-mode action dock.
- Give the shared public footer an emergency-operations information-desk
  treatment and improve mobile login alignment and safe-area spacing.
- Show current account names, initials, and profile photos in the remembered
  account chooser. Profile photo URLs are resolved from the active user
  record and are not stored in the remembered-account cookie.
- Bump the service-worker cache version so updated public scripts are
  retrieved.

### Verification

- `npm run build`
- `php artisan view:cache`
- PHP and JavaScript syntax checks
- `git diff --check`
- Targeted quick-login tests: 8 passed (51 assertions)
- Community dashboard test: 1 passed (7 assertions)
- Browser checks for theme persistence across navigation/login, dark/light
  dashboard chart colors, report wizard controls, page transition, and
  responsive dashboard/login layouts

No database schema or incident/account records were changed.
