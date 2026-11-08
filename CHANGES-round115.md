# Round 115 - Dashboard and records refinements

## Changed

- Separate same-named open incident locations into Inside Pamplona and Outside Pamplona dashboard counts, using the incident's jurisdiction classification.
- Remove Closed from the admin dashboard status chart and summary text while retaining existing incident lifecycle behavior.
- Remove the redundant status filter from Case Documents; resolved and closed cases remain available together.
- Narrow date filters on desktop and hide Incident type when the Reports Studio is showing the evacuee registry.
- Generate document-request PDF links relative to the current site domain across admin and agency views and notification fallbacks.

## Verification

- Targeted dashboard, case-document, report-filter, and document-request tests passed (19 tests, 190 assertions).
- Pint passed for the new model helper and dashboard jurisdiction test; `git diff --check` passed.
