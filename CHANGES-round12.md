RANIAG changelog — Round 12

Overview
This round closes the remaining login and authentication regressions discovered after the previous UI and appearance updates. It also finishes the mobile navigation cleanup that was still causing the sidebar to close unexpectedly when a dropdown was opened and making the bottom navigation feel slow on phones. The update fixes the test-database migration incompatibility, restores the missing baseline seed data used by the dashboard and auth checks, stabilizes the stale-CSRF/back-button flow on auth routes, and confirms the complete auth suite is passing again.

1) Mobile sidebar and dock polish

The mobile bottom nav was feeling sluggish because the dock styling was still doing more visual work than necessary on a small touch interface. The dock was simplified to reduce unnecessary transition overhead and to add touch-action: manipulation so taps respond immediately without the heavier hover-driven effects.
The sidebar close handler was also too aggressive. It was closing the off-canvas sidebar on any nav-link click, even when the click was meant to open a submenu or collapse section. The logic now excludes collapse triggers and hash-only links, so dropdowns inside the sidebar stay open while real navigation still closes the sidebar on mobile.
This resolves the irritating "dropdown closes immediately" behavior and makes the mobile bottom navigation feel noticeably sharper during day-to-day movement around the app.

2) Login and authentication recovery

The app was failing to authenticate in the real project flow because several migrations used MySQL-only ALTER TABLE ... MODIFY statements that break when the app runs against SQLite in the test environment. That blocked the auth test suite from setting up the schema reliably.
Fixed by guarding the MySQL-only migration SQL behind a database driver check so SQLite-based tests and local validation work without crashing during migration.
This restored the expected login and session flow and removed the false auth failure that was masking the real issue.

3) Baseline dashboard data restoration

The auth feature checks also expected seeded baseline agencies and incident types to exist. The agency seeder was skipping creation in local/test environments and leaving the app with no agency rows to validate against.
The seeder now creates the required default agency records in local and test environments and still seeds the agency officer accounts needed for role-based access checks.
This brings the dashboard data expectations back in line with the actual app logic and allows the login tests to validate real behavior.

4) Stale CSRF and browser-cache protection for auth routes

The app was still vulnerable to stale-token behavior on login, password reset, and logout after browser back/forward navigation or expired page cache states.
The auth routes were tightened so those endpoints are resilient against stale CSRF tokens while preserving normal protection for the rest of the app.
This prevents Page Expired and session mismatch problems that can interrupt the normal sign-in and sign-out flow.

5) Password reset flow repair

The password reset flow was still not matching Laravel's expected notification contract during the feature tests. The app had custom code that bypassed the default reset-password notification class and caused the notification fake assertions to fail.
The reset-password flow was aligned with Laravel's standard notification behavior while keeping the branded RANIAG email template in place.
This fixed the reset-link request assertion and confirmed the full password reset path works as intended.

6) Validation

The fix was validated by running the auth feature suite:
php artisan test tests/Feature/Auth --compact

Result: 21 auth tests passed, 47 assertions, exit code 0.

This includes:
- login screen rendering and redirect checks
- active/inactive account validation
- invalid password rejection
- logout behavior
- email verification
- password confirmation
- password reset request and token reset flow
- password update validation
- registration disabled state

Deployment notes

No additional database migration is required for the app itself beyond the existing project migrations. If deploying to Hostinger, run the normal Laravel cache refresh after upload:
- php artisan optimize
- php artisan route:clear
- php artisan view:clear
- php artisan config:clear

Files changed
- resources/views/components/mobile-dock.blade.php
- resources/views/layouts/app.blade.php
- database/migrations/2026_07_29_000000_widen_incident_reporter_contact_columns.php
- database/migrations/2026_08_04_000001_fix_incident_timestamp_columns.php
- database/seeders/AgencySeeder.php
- routes/auth.php
- app/Models/User.php
- app/Notifications/ResetPasswordNotification.php
