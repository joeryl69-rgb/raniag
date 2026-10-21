# Round 92 — Reports stay on one gathered list

## Changed

- Saved reports is no longer a page on Reports. The Report home and Saved reports tabs are gone, and the old saved-reports address returns to the gathered report page.
- The report page opens with the barangay, incident type, and dates empty. The month-to-today range is no longer filled in, so the page does not show active filters before anyone chooses them.
- The barangay list includes Outside Pamplona. That choice covers reports referred outside the area of responsibility, reports pinned outside the municipality, and reports whose place is not a Pamplona barangay. Leaving the barangay on All barangays still includes those reports with the rest.
- Records can be Incident reports or the Evacuee registry. The registry uses the same barangay and date filters, including Outside Pamplona, and shows the shelter list: name, shelter, where the person is from, age and sex, check-in, and whether they are still inside. Incident type applies only to incident reports.
- Excel, Word, PDF, and Print follow whichever records are selected. Excel still uses two sheets, with the charts on the second sheet. The letterhead and the repeating table heading stay with the printed table.

## Verification

- The gathered list, empty opening dates, outside reports, the evacuee registry Excel sheet, the removed saved-reports address, and the offline submission notice passed together: **12 tests, 130 assertions**.
- The signed-in Reports page was checked through the admin test session. A browser still stops at the email code.
