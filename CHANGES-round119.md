# Round 119 - Investigation requests and live updates

## Changed

- Preserve both requested details and investigation-log notes when an agency or responder asks the administrator for information.
- Keep pending-info updates private to staff while showing their request and log separately to administrators.
- Initialize the requested-details field from the current investigation phase after page load.
- Poll the administrator's response panel and add a cache-busting query to live-refresh requests so new updates are fetched from the server.

## Verification

- Investigation update and public tracking tests passed (11 tests, 147 assertions).
- Pint, PHP/Blade syntax checks, JavaScript syntax check, and `git diff --check` passed.
