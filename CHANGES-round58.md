# RANIAG Changelog

## 2026-10-06 — Public emergency-operations redesign

This release replaces the public-facing RANIAG experience with a cohesive
emergency-operations visual system for MDRRMO Pamplona. The changes cover
public reporting, public information, tracking, support, and staff sign-in
surfaces; internal staff dashboards are not redesigned.

### Public site and navigation

- Rebuilt the public home page around a Pamplona-specific safety desk, clear
  report and tracking actions, and a four-stage, scroll-led explanation of
  how a report moves from intake to follow-through.
- Added motion to the report journey, field-status and map-inspired visual
  details, app-install presentation, community updates, FAQs, and support
  entry points.
- Made the background respond visibly to pointer movement in light and dark
  themes. It does not run on touch-only devices or when reduced motion is
  requested.
- Replaced the browser-default page transition with a brief page-content
  fade/lift and a restrained progress accent. It respects reduced-motion
  preferences and leaves modified-click, external, and download links alone.
- Removed Support Center from the navigation while retaining the dedicated
  help button and in-page links.
- Simplified the top information strip to “RANIAG / PUBLIC REPORTING” and
  “Pamplona, Cagayan,” avoiding a repeated organization label.
- Added responsive layouts and safe-area spacing to public controls and JO
  guide artwork.

### Report flow and JO guide

- Refreshed the multi-step report wizard, step navigation, and location
  guidance while preserving existing form fields, routes, validation, and
  submission behavior.
- Reworked the missing-evidence prompt and outside-jurisdiction notice into
  concise, actionable, contextual messages.
- Added field-level error cues and made wizard controls and evidence guidance
  clearer on small screens and in dark mode.
- Replaced legacy JO guide presentation with responsive, themed guide panels
  and the supplied transparent JO illustrations.
- Made the mobile site tour open the navigation menu when it needs to point
  out a navigation link, and close that menu when the tour ends.

### Tracking, map, dashboard, and support

- Redesigned public report lookup and the tracking case/status presentation,
  including the progress path and incident details.
- Updated the Community Dashboard, Live Map guidance, and Support Center to
  use the shared public visual language and JO assets.
- Kept individual tracking details private to the tracking page; the public
  dashboard continues to show aggregate information.

### Theme, accessibility, and security

- Centralized the public light/dark preference and synchronize it across
  public pages, browser tabs, and login/auth screens.
- Removed the independent theme switch from the login/auth shell; it now
  inherits the preference selected on the public site.
- Improved dark-theme contrast for links, cards, card headers, body text,
  buttons, modal content, and JO guide controls, replacing low-contrast blue
  text with legible teal/light tones.
- Added reduced-motion behavior to decorative motion and page transitions,
  preserved keyboard-accessible controls, and checked mobile overflow.
- Allowed `https://nominatim.openstreetmap.org` in the Content Security
  Policy `connect-src` directive for GPS reverse geocoding.
- Bumped the service-worker cache version so browsers can retrieve updated
  public assets.

### Data integrity

- No production database was seeded, migrated, or modified as part of the
  redesign work.
- A separate preview used temporary SQLite storage. A read-only check of the
  configured XAMPP MySQL database found 8 active incident types, 6 incidents,
  and 6 user accounts; those records were not changed.

### Verification

- `npm run build`
- `php artisan view:cache`
- PHP and JavaScript syntax checks
- `git diff --check`
- Browser smoke checks for public routes, light/dark themes, report cards,
  mobile navigation/tour, cursor response, login theme inheritance, and
  responsive report presentation

### Deployment

Pushing the release to `main` triggers `.github/workflows/deploy.yml` for
`mediumorchid-weasel-407759.hostingersite.com`. Verify the deployment and
public site after the workflow completes.
