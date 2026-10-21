# Round 103 — Reverting searchable dropdowns

## Changed

- Removed the custom searchable dropdown behavior from the staff and public layouts and restored native select controls throughout the application.
- Removed the custom searchable control from the gathered Reports barangay field and cleaned up its associated styles and script.
- Kept the separate From/To calendar redesign; it is not part of the searchable select behavior.

## Follow-up

- The dropdown visual redesign was restored without its search field. See [CHANGES-round104.md](./CHANGES-round104.md).

## Verification

- `IncidentPresentationTest`, `FilterWorkflowTest`, and `IncidentInterfaceTest`: **32 passed**.
- Blade syntax checks and `git diff --check` passed.
