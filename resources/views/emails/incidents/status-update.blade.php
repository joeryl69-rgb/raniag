<x-mail-shell title="Incident report update">
<h2 style="margin:0 0 12px; font-size:19px; color:#10203a;">Your Incident Report Has an Update</h2>
<p style="margin:0 0 20px; color:#475569; font-size:14px; line-height:1.6;">There is a new update for report <strong>{{ $incident->tracking_number }}</strong>.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; border-radius:10px; margin:0 0 20px;">
<tr><td style="padding:14px 18px;">
<p style="margin:0; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:700;">Current status</p>
<p style="margin:8px 0 0; color:#10203a; font-weight:700; font-size:16px;">{{ $incident->status->label() }}</p>
<p style="margin:6px 0 0; color:#334155; font-size:13.5px; line-height:1.6;">{{ $updateMessage }}</p>
</td></tr>
</table>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
<tr><td style="border-radius:10px; background:#0b5ed7;">
<a href="{{ $trackUrl }}" style="display:inline-block; padding:14px 22px; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none;">
View live status
</a>
</td></tr>
</table>
<p style="margin:0; color:#94a3b8; font-size:12.5px; line-height:1.6;">
Or open: <span style="word-break:break-all; color:#64748b;">{{ $trackUrl }}</span>
</p>
</x-mail-shell>
