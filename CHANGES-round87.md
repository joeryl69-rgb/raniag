# Changelog

All notable changes to RANIAG are documented here.

## [Unreleased] - 2026-10-13

### Added

- Added per-user dashboard incident alerts for administrators, agencies, and personnel. Alerts are retrieved from durable notifications, polled independently of dashboard analytics, queued during alert bursts, and can be opened or dismissed.
- Added automatic refresh for public incident tracking, staff case status and activity timelines, and responder map locations. Map refreshes also run when a page becomes visible again.
- Added GPS location fallback behavior that can use a recent or network-based position while high-accuracy GPS continues refining the fix.
- Added visible approval and rejection feedback to the admin document-request page, including distinct messaging when PDF email delivery fails.
- Added explicit Apply and Clear behavior, active-filter indicators, and removable filter chips to shared filters and incident queues.
- Added regression coverage for dashboard alert delivery and account isolation, live tracking and dispatch locations, evidence formatting, document-request feedback, and filter behavior.

### Changed

- Redesigned incident case pages for administrators, agencies, and personnel. Desktop layouts place related case information into more consistent columns; narrow screens retain a stacked layout.
- Updated agency and personnel case pages to keep evidence and activity in a shared support column, reducing blank gaps when the action panel changes height, including after a case is resolved.
- Standardized evidence preview heights and card sizing so portrait video or image dimensions do not stretch neighboring evidence cards.
- Improved GPS evidence address formatting to include available locality details without inventing an unresolved barangay.
- Enlarged the GPS watermark map and pin, added a fallback when map tiles are unavailable, and improved readability for longer location text.
- Improved GPS camera acquisition responsiveness while retaining high-accuracy location updates for refinement.
- Improved the public tracking page's responding-agency panel and phase badges for dark mode.
- Unified GPS photo and video watermark presentation. Video capture can include microphone audio when permission is granted.
- Updated incident status and dispatch map presentation to keep agency, administrator, and public tracking information aligned.
- Restored consistent pagination presentation and improved the desktop Add Incident Type dialog's scrolling behavior.
- Updated dashboard alert presentation with clearer animation and sound cues, responsive layout, accessible announcements, and reduced-motion support.

### Fixed

- Fixed dashboard alerts being suppressed or missed when a browser-local seen-ID value or a single latest-incident value was reused across accounts or alert bursts.
- Fixed public tracking and staff case pages requiring a manual refresh to show status, assignment, response, and activity updates.
- Fixed GPS location labels omitting available barangay, municipality, province, or country information.
- Fixed GPS watermark maps and location pins being too small or disappearing when map tiles fail to load.
- Fixed document-request actions returning to a page without a clear visible result.
- Fixed filter controls submitting immediately when a filter selection or chip was changed; filters now run only after explicit application.
- Fixed oversized case action controls and excessive stretching or gaps in evidence cards on agency/personnel case pages.
- Fixed dark-mode contrast for the public tracking responding-agencies panel.

### Verification

- Focused regression tests for document-request feedback, evidence, incident interfaces, live tracking, hazard maps, and filters passed: **23 tests, 207 assertions**.
- Follow-up incident, evidence, and assignment regression tests passed: **13 tests, 121 assertions**.
- PHP and JavaScript syntax checks and `git diff --check` passed for the verified changes.
- Device-specific GPS behavior and final browser rendering still require physical-device and browser verification.
