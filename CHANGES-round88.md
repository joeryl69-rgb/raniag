# Round 88 — Needed queue sorts and table/card views

## Changed

- The administrator incident queue keeps column sorting only on Priority and Reported. Incident / tracking, Response status, and Location stay as labels because search and filters already cover them.
- Incident, agency, and personnel queue filters now offer the same two sorts: Priority and Reported time, with Newest first or Oldest first.
- Table / Cards stays on the live incident queue, and on the evacuee registry when someone is listed.
- Table / Cards is removed from registers that only need a table: agency directory, personnel directory, incident types, case documents, SMS alerts, audit trails, agency document-request history, and resolved reports. Sorting on those tables is unchanged.

## Verification

- Incident queue, workspace sort, modal, and evacuee registry tests passed: **14 tests, 204 assertions**.
- Staff sign-in from an automated browser stops at the email code, so the visual check still needs a signed-in session.
