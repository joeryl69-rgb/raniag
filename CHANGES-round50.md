# RANIAG — Round 50 Changes

Feedback & Concerns no longer mixes the status menu into the message.
Each submission is a case the admin can read, then update, then reply to.

=====================================================================
1. Case layout
=====================================================================
A submission shows the subject, where it came from, who sent it, and
the message. The current status is a label on the case: red for new,
amber for reviewed, green for resolved. A long emailed reply stays in
a short scroll box so it does not cover the next case.

Files:
  resources/views/admin/feedback/index.blade.php

=====================================================================
2. Status, note, and reply
=====================================================================
Status is three choices — New, Reviewed, Resolved — instead of a
dropdown that opened over the next message. The internal note is
labeled as staff-only and is not emailed. Save stores the status and
the note, the same way as before.

Email a reply stays a separate action. If the sender left no email,
the case says a reply cannot be sent. Sending a reply still uses the
same editor and still marks a new case as reviewed.

Files:
  resources/views/admin/feedback/index.blade.php

=====================================================================
Verify after Hostinger deploy
=====================================================================
- Open Admin, Feedback & Concerns.
- A new case shows New, Reviewed, and Resolved as choices, not a
  dropdown on top of the message.
- Save with Reviewed selected. The case label changes to Reviewed.
- A case with no email says a reply cannot be sent.
- A case with an email still opens the reply window.
