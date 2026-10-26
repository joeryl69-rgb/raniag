# Round 107 - First-visit offline reporting and manual location pins

## Changed

- The offline landing page checks whether this phone has a cached report form or can reach the server. The Report an incident button is disabled with an explanation when neither is available, and enabled when the form can actually open.
- When the report form is available offline, reporters can upload a regular photo as evidence and tap the map to set an approximate pin. They can drag the pin to adjust it.
- Offline manual pins use locally available barangay boundaries and coordinates; reverse-address lookup is skipped without a connection. If no pin can be set, the existing offline location-unavailable option remains available.
- Clarified that map imagery may not load offline and that a manually selected pin is approximate.

## Verification

- Focused offline-report feature tests passed.
- JavaScript syntax checks and `git diff --check` passed.
