# Round 112 - Evacuee registry pagination and clean presentation

## Changed

- Added 15-row pagination to the admin evacuee registry, preserving shelter and record filters between pages.
- Made search, stay status, and origin filters apply across the selected registry rather than only the rows loaded in the browser.
- Removed presentation-only labels and warnings from evacuee names, registry rows, and report summaries; existing seeded rows are cleaned by migration.
- Removed generated vulnerability labels from seeded presentation records.

## Verification

- Targeted evacuee registry and report presentation tests passed.
- PHP formatting, production asset build, and diff checks passed.
