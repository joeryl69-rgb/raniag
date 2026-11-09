# Round 118 - Tracking updates and responder maps

## Changed

- Keep the quick-login form to one submission when an account is double-clicked.
- Show every involved responder's resolution summary and completion time on the public tracking page.
- Disable caching for tracking-page polling so status, agency, and resolution updates appear without a manual refresh.
- Keep the public live map framed around the incident and responders as they move, unless the viewer manually pans the map.
- Confirm staff maps poll live locations and the public tracking map polls responder locations independently of the mobile map-touch shield.

## Verification

- Tracking, map movement, and quick-login regression suites passed (21 tests, 216 assertions).
- Modified PHP and JavaScript syntax checks passed; `git diff --check` passed.

## Deployment

- These changes are local and must be deployed to Hostinger before they appear on the live site.
