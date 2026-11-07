# Round 110 - Allow manually uploaded video evidence

## Changed

- Fixed a mismatch where the report form accepted uploaded videos but both browser-side and server-side validation rejected videos unless they were recorded with the GPS Camera.
- Manual video files can now be submitted with other evidence. They remain ordinary evidence and are not marked as GPS captures; a report still needs a valid location when online.
- Clarified that GPS coordinates are attached only to video recorded with the GPS Camera.

## Verification

- Public incident-report and offline-report feature tests passed (31 tests).
- `node --check public/js/public-report.js` passed.
- `npm run build` passed.
- PHP Pint formatting check and `git diff --check` passed.
