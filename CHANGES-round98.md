# Round 98 — Evacuee registry controls

## Changed

- Moved the evacuee registry search field to the left, beneath the count and view controls, for a clearer table toolbar.
- Kept the All / Pamplona / Outside origin toggle visible alongside the right-aligned search field so the roster can be narrowed by origin or text.
- Search filters by evacuee name, shelter, and home location and composes with the selected origin.

## Verification

- `php artisan test tests/Feature/Admin/OutsideEvacueeTest.php` passed.
- PHP syntax checks and `git diff --check` passed.
