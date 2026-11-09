# Round 120 — Public tracking updates and pending-information privacy

- Public tracking no longer shows internal Pending Information status updates or their request/investigation notes, including legacy updates incorrectly marked public.
- Pending Information is presented as In Progress on the public status card, and the public reply prompt is removed.
- Agency and personnel resolution summaries now refresh through a dedicated, no-store history endpoint every three seconds, without a manual page refresh.
- The history endpoint requires the verified tracking session.
- Staff history displays requested details and investigation notes as separate, clearly labeled items.

## Verification

- `php artisan test tests/Feature/Public/LiveUnitsAndRiskTest.php tests/Feature/InvestigationUpdateTest.php`
- `node --check public/js/public-track-history.js`
