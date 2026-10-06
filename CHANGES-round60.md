# Round 60 — Faster public navigation and report submission

- Shortened public page transitions to an 80 ms navigation handoff and a brief, lightweight arrival animation.
- Kept the public theme control label stable as “Theme” and synchronized the applied theme with the saved preference when page content is ready.
- Removed the server-side Nominatim lookup from GPS-photo watermarking. Watermarks now use the report's supplied address, or the locally resolved barangay and configured municipality details.
- Added a clear long-request message to the report submission overlay so people know to keep the page open and avoid resubmitting while processing continues.
