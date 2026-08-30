<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Response to your feedback</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#111; background:#f7f7f7; padding:20px;">
    <div style="max-width: 640px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px;">
        <h2 style="margin-top:0;">RANIAG &mdash; Response to Your {{ $feedback->categoryLabel() }}</h2>

        <p>Hello {{ $feedback->submitter_name ?: 'there' }},</p>

        <p>Thank you for reaching out. Here is our response regarding your submission:</p>

        <div style="background:#f1f5f9; border-radius:8px; padding:12px 16px; margin:8px 0 20px;">
            <p style="margin:0; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.03em;">Your original message</p>
            <p style="margin:6px 0 0; font-weight:600;">{{ $feedback->subject }}</p>
            <p style="margin:6px 0 0; color:#334155; white-space:pre-line;">{{ $feedback->message }}</p>
        </div>

        <div style="border-left: 3px solid #0d6efd; padding-left: 16px;">
            {!! $replyHtml !!}
        </div>

        <hr style="border:none; border-top: 1px solid #eee; margin-top:24px;" />
        <p style="color:#777; font-size: 12px;">
            This message was sent by {{ config('raniag.name') }} in response to feedback submitted through the public portal.
            Please do not reply directly to this email.
        </p>
    </div>
</body>
</html>
