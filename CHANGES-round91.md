# Round 91 — Simple incident reports, offline notice, map scrolling

## Changed

- The Reports page is now one gathered-incident presentation. The administrator picks a barangay, an incident type, and a from/to date with the same filter bar used on the other lists, then applies it. The matching reports open as a table, and Table / Cards switches that same list. Excel, Word, PDF, and Print use those same rows.
- Excel has two sheets. Reports is the table, with the office heading repeated when the sheet is printed. Charts is a bar chart for incident type, barangay, and status.
- Word, PDF, and Print use the same letterhead: Republic of the Philippines, Province of Cagayan, Municipality of Pamplona, and MDRRMO Pamplona / RANIAG Operations Center, with both seals. The column headings repeat on each printed page, and a row is kept together so the heading does not split.
- Saved reports, the situation review, and a specific-request draft stay available from their existing pages. They are no longer the front of Reports.

## Added

- On the public report form, “Notify this phone when an offline report is received” subscribes that browser with the site VAPID key. When a report that was saved offline is accepted, RANIAG sends “Report submitted” with the tracking number. A report sent while online does not send that notice.
- Maps on the live map, the staff dashboard, the hazard desk, a case page, public tracking, and the report form let the page scroll. On a phone, a finger moves the page until the person taps “Tap to use the map”; “Back to scrolling” returns that behavior. On a desktop, the wheel scrolls the page until Ctrl is held.

## Verification

- The filtered table, Excel workbook (Reports and Charts sheets), Word letterhead, print page, PDF, and offline VAPID notice tests passed: **7 tests, 79 assertions**. The hazard page still loads after the Reports landing change.
- On a phone-sized live map, the overlay reads “Tap to use the map”, and tapping it switches to “Back to scrolling”. The report form shows “Notify this phone when an offline report is received”.
- The staff Reports page itself was checked through the admin test session. A signed-in browser still stops at the email code.
