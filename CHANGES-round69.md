# Round 69 — Operations workspace refresh

- Added a shared, theme-token-aware operations page heading and workspace stylesheet for the staff portal. The ambient page surface, data tables, KPI summaries, form controls, tabs, and list rows follow the signed-in user's light, dark, or system color mode.
- Refreshed the admin Dashboard, Notifications, Hazard Map, Case Documents, Incident Types, QR Posters, Reports, Agencies & Personnel, Document Requests, SMS Alerts, Audit Trails, Feedback & Concerns, and Updates & Announcements pages with role- and task-specific page headings while preserving their existing actions and workflows.
- Restyled the hazard-map summary metrics, map workspace, and control panel to use the shared theme colors and clearer visual hierarchy. The public map link remains available from the page heading.
- Kept incident queue/detail views aligned across administrator, agency, and personnel accounts, tightening case-panel spacing and responsive card rhythm without changing assignment or response actions.
- Preserved the printer-specific QR poster output and existing report/PDF workflows.
- Verification: Blade templates compiled and cache was cleared; 17 relevant incident, assignment, hazard, announcement, notification, and agency document-request tests passed (140 assertions); `git diff --check` passed.
