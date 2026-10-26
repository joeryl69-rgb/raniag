# Round 108 - Simplifying offline report access

## Changed

- The offline page no longer waits for a network-health request before enabling the report link when connected. Offline, it checks only whether this browser has a saved report form.
- When connected, the offline page asks the service worker to cache the report form for the next connection drop.
- Versioned the service worker registration URL and made public and staff pages use the same worker URL so clients are prompted to install updated offline behavior.
- Kept the limitation explicit: a browser with no saved report form cannot open a full report form on a first-ever offline visit.

## Verification

- Offline report feature tests cover immediate online availability, cached-form checking, cache preparation, and consistent service-worker registration.
- JavaScript syntax checks and `git diff --check` passed.
