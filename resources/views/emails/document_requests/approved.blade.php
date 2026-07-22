<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Printable Document Approved</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#111; background:#f7f7f7; padding:20px;">
    <div style="max-width: 640px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px;">
        <h2 style="margin-top:0;">RANIAG — Printable PDF Approved</h2>

        <p>
            Hello,
        </p>

        <p>
            Your request printable PDF has been approved and is ready for download.
        </p>

        <p>
            <strong>Tracking Number:</strong> {{ $tracking_number }}
        </p>

        <p style="color:#444; font-size: 13px;">
            If you have questions, please check the RANIAG system for the full incident details.
        </p>

        <hr style="border:none; border-top: 1px solid #eee;" />
        <p style="color:#777; font-size: 12px;">
            This message was generated automatically by {{ config('raniag.name') }}.
        </p>
    </div>
</body>
</html>

