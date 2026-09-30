<x-mail-shell title="Your login verification code">
<h2 style="margin:0 0 12px; font-size:19px; color:#10203a;">Sign-in verification</h2>
<p style="margin:0 0 18px; color:#475569; font-size:14px; line-height:1.6;">
Hi {{ $name }}, use this code to finish signing in to the RANIAG staff portal.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">
<tr>
<td align="center" style="background:#f4f8ff; border:1px solid #d6e4fb; border-radius:12px; padding:18px 16px;">
<p style="margin:0 0 6px; color:#64748b; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">Verification code</p>
<p style="margin:0; color:#0b5ed7; font-size:34px; font-weight:800; letter-spacing:8px; font-family:Consolas, 'Courier New', monospace;">{{ $code }}</p>
</td>
</tr>
</table>
<p style="margin:0 0 8px; color:#334155; font-size:14px; line-height:1.6;">
This code expires in {{ (int) config('raniag.two_factor.otp_ttl_minutes', 10) }} minutes.
</p>
<p style="margin:0; color:#94a3b8; font-size:12.5px; line-height:1.6;">
If you did not try to sign in, ignore this email. Your account stays unchanged.
</p>
</x-mail-shell>
