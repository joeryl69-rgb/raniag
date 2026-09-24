<x-mail-shell title="Report received">
<h2 style="margin:0 0 12px; font-size:19px; color:#10203a;">We received your report</h2>
<p style="margin:0 0 20px; color:#475569; font-size:14px; line-height:1.6;">
Thank you for reporting to {{ config('raniag.organization') }}. Your tracking number is
<strong>{{ $incident->tracking_number }}</strong>. Use the button below to open your live status page — no need to type the number.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; border-radius:10px; margin:0 0 22px;">
<tr><td style="padding:14px 18px;">
<p style="margin:0; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:700;">Tracking number</p>
<p style="margin:8px 0 0; color:#10203a; font-weight:800; font-size:18px; letter-spacing:.04em;">{{ $incident->tracking_number }}</p>
@if ($incident->incidentType)
<p style="margin:8px 0 0; color:#334155; font-size:13.5px;">{{ $incident->incidentType->name }} · {{ $incident->status->label() }}</p>
@endif
</td></tr>
</table>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
<tr><td style="border-radius:10px; background:#0b5ed7;">
<a href="{{ $trackUrl }}" style="display:inline-block; padding:14px 22px; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none;">
Open tracking page
</a>
</td></tr>
</table>
<p style="margin:0; color:#94a3b8; font-size:12.5px; line-height:1.6;">
If the button does not work, copy this link into your browser:<br>
<span style="word-break:break-all; color:#64748b;">{{ $trackUrl }}</span>
</p>
</x-mail-shell>
