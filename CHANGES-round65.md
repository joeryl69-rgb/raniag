# Round 65 — Offline report submission

- Submitting a public incident report with no connection saves the report on that phone, photos included, and sends it when the connection returns. A dropped signal during submit is treated the same way. The page no longer waits for the browser to already say “offline.”
- That notice is a dialog on the report page (“Saved on this phone”), not a browser alert. If the phone cannot keep the report, the same dialog says so.
- A saved report sends itself from this browser when the signal returns, then opens the tracking success page. Sending it twice does not create two cases.
- The offline page tells the reporter that a report already started on this phone is kept and sent later. Tracking still needs a connection.
