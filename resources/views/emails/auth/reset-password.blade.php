<x-mail-shell title="Reset your password">
<h2 style="margin:0 0 12px; font-size:19px; color:#10203a;">Reset your password</h2>
<p style="margin:0 0 20px; color:#475569; font-size:14px; line-height:1.6;">
We received a request to reset the password for your RANIAG staff account. Click the button below to choose a new one.
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
<tr><td style="border-radius:10px; background:linear-gradient(135deg,#1671e8,#0b5ed7);">
<a href="{{ $resetUrl }}" style="display:inline-block; padding:13px 28px; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none;">Reset Password</a>
</td></tr>
</table>
<p style="margin:0 0 6px; color:#94a3b8; font-size:12.5px; line-height:1.6;">
This link will expire in 60 minutes for your security.
</p>
<p style="margin:0; color:#94a3b8; font-size:12.5px; line-height:1.6;">
If you didn't request this, you can safely ignore this email — your password will remain unchanged.
</p>
</x-mail-shell>
