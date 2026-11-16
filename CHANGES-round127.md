# Round 127 - Municipality referral directory and public coordination details

- Added an administrator-managed directory for multiple offices in nearby municipalities, including phone, email, public hotline, address, active state, and optional map coordinates.
- Added one-click call and email actions when an administrator selects a directory contact on an Outside AOR incident.
- Outside-AOR referrals now save a snapshot of the selected municipality, office, hotline, location, and public coordination note with the case.
- Public tracking shows the referral details and a click-to-call hotline, and adds a separate receiving-office pin to the live response map. If the report has no GPS point, it shows a dedicated map for the receiving office instead.
- The public tracking poll refreshes referral details and keeps the optional map pin in sync without exposing the directory's private phone or email fields.

## Verification

- `php artisan test --compact tests/Feature/Admin/MunicipalityReferralTest.php tests/Feature/Public/LiveUnitsAndRiskTest.php`
- JavaScript syntax check for the public tracking map.
- PHP syntax checks and `git diff --check`.
