# Round 129 - Editable Pamplona municipal boundary

- Added an administrator-only map editor to adjust or redraw Pamplona’s single municipal boundary.
- Persisted the active outline in shared system settings so report jurisdiction checks, public reporting and hazard maps, and all staff dashboard overlays use the same boundary.
- Added an explicit reset action that restores the original GeoJSON boundary without changing barangay borders.
- Recorded boundary updates and resets in the audit log, including the administrator and edited vertex count.
- Made the active municipal outline authoritative over static barangay borders in GPS camera and public-report location resolution, the public barangay lookup, and server-side report classification.
- Shared the client-side polygon resolver between the report form and GPS camera to keep outside-boundary handling consistent.

## Verification

- `php artisan test --compact tests\Feature\Admin\MunicipalityGeofenceTest.php tests\Unit\GeofenceServiceTest.php tests\Feature\Admin\DashboardJurisdictionTest.php tests\Feature\Public\HazardMapTest.php`
- Focused PHP tests plus Node.js regression tests for custom-boundary precedence, PHP formatting and syntax checks, JavaScript syntax checks, and `git diff --check`.
