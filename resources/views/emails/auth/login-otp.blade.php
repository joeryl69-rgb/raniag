<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Login code</title></head>
<body style="font-family: system-ui, sans-serif; color: #1a1a1a;">
    <p>Hi {{ $name }},</p>
    <p>Your RANIAG login verification code is:</p>
    <p style="font-size: 28px; letter-spacing: 6px; font-weight: 700;">{{ $code }}</p>
    <p>This code expires in {{ (int) config('raniag.two_factor.otp_ttl_minutes', 10) }} minutes. If you did not try to sign in, ignore this email.</p>
</body>
</html>
