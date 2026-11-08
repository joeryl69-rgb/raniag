# Round 111 - Capacity-aware shelter registry and demo records

## Changed

- Added idempotent demo evacuee seeding for open shelters, with a varied count based on each shelter's capacity and a maximum of 30 per shelter.
- Clearly labeled synthetic Philippine-name demo records, including a sample of guests from nearby municipalities. Demo rows are identified in the registry and reports and count toward capacity until checked out.
- Enforced open-shelter and capacity checks when checking in an evacuee, with a locked shelter row to prevent concurrent over-capacity check-ins.
- Added an all-shelters or individual-shelter filter to the Hazard Map evacuee registry and the Evacuee Registry presentation/report exports.
- Updated the Hostinger deployment workflow to run the idempotent demo seeder after migrations.

## Verification

- Targeted shelter, report-presentation, and seeder tests passed.
- PHP formatting, JavaScript syntax, production asset build, and `git diff --check` passed.
