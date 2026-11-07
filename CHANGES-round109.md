# Round 109 - Mobile views, case filters, and report uploads

## Changed

- Made the administrator hazard-map evacuee filters stack clearly on small screens, with each filter group and the search field using the available width.
- Added distinct card layouts for the incident queue on desktop and mobile while preserving its compact mobile table layout.
- Added inside/outside Pamplona filtering to the Case Documents register.
- Raised PHP and Nginx request limits to support the report form's allowed multiple evidence files in one submission. Added PHP-FPM per-directory overrides for Hostinger and matching PHP configuration for Docker.

## Verification

- `php artisan test tests/Feature/Admin/IncidentDocumentFilterTest.php tests/Feature/Admin/OutsideEvacueeTest.php tests/Feature/Public/IncidentReportTest.php` passed (26 tests).
- `npm run build` passed.
- `vendor/bin/pint --test app/Http/Controllers/Admin/IncidentDocumentController.php tests/Feature/Admin/IncidentDocumentFilterTest.php tests/Feature/Public/IncidentReportTest.php` passed.
- `git diff --check` passed.
