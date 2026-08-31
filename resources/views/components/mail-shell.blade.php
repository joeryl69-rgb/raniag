@props(['title' => 'RANIAG'])
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background:#eef3fb; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3fb; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(11,94,215,0.10);">

<!-- Header -->
<tr>
<td style="background:linear-gradient(135deg,#1671e8,#0b5ed7 55%,#073a86); padding:28px 32px; text-align:center;">
<img src="{{ config('app.url') }}/images/icons/icon-96x96.png" width="48" height="48" alt="RANIAG" style="display:block; margin:0 auto 10px; border-radius:12px;">
<div style="color:#ffffff; font-size:20px; font-weight:800; letter-spacing:.02em;">RANIAG</div>
<div style="color:#ffe8a3; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; margin-top:2px;">{{ config('raniag.organization') }}</div>
</td>
</tr>

<!-- Body -->
<tr>
<td style="padding:32px;">
{{ $slot }}
</td>
</tr>

<!-- Footer -->
<tr>
<td style="padding:18px 32px 26px; border-top:1px solid #eef1f5;">
<p style="margin:0; color:#94a3b8; font-size:11px; line-height:1.6;">
This message was sent automatically by RANIAG — {{ config('raniag.organization') }}. Please do not reply directly to this email.
</p>
<p style="margin:6px 0 0; color:#c3cbd6; font-size:11px;">&copy; {{ now()->year }} RANIAG. All rights reserved.</p>
</td>
</tr>

</table>
</td></tr>
</table>
</body>
</html>
