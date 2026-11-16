# Round 128 - Referral contact directory interface

- Replaced the always-visible contact form with a full-width directory table and a shared add/edit modal.
- Grouped office details, contact channels, optional map-pin selection, and availability controls into focused sections.
- Removed visible latitude/longitude fields while preserving optional pin selection on the map.
- Ensured the page's styles and modal scripts are pushed before the application layout renders them.

## Verification

- `php artisan test --compact tests\Feature\Admin\MunicipalityReferralTest.php tests\Feature\Public\LiveUnitsAndRiskTest.php tests\Feature\IncidentStatusTransitionTest.php`
- `git diff --check`
