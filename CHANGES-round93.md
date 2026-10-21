# Round 93 — Report print, PDF, and Excel

## Changed

- The report column headings use dark navy text on a light band, so they stay readable when a printer does not paint background colors.
- Print opens in a preview on the Reports page. Paper can be A4, Letter, Legal, or Folio, in landscape or portrait. The same choice is used for Excel, Word, and PDF. The preview is the PDF itself, so the printed pages do not add the browser address, the date line, or a page number.
- The two seals on the report heading are larger, including on the other generated report files that share that heading. Place names wrap on spaces instead of breaking in the middle of a word, and the table columns give the place and status room so a row stays together.
- Excel can be the table alone, or the table plus the charts sheet. The charts sheet is valid workbook XML, so Excel no longer has to repair the file.

## Verification

- The filtered report, the table-only Excel file, the Excel file with charts, Word, the print page, and PDF passed: **5 tests, 78 assertions**.
