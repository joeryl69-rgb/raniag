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
<p style="margin:0; color:#475569; font-size:14px; line-height:1.6;">You can use your tracking number on the RANIAG tracking page to view the latest public status.</p>
</x-mail-shell>
