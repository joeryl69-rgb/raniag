<x-mail-shell title="Response to your feedback">
<h2 style="margin:0 0 12px; font-size:19px; color:#10203a;">Response to Your {{ $feedback->categoryLabel() }}</h2>
<p style="margin:0 0 6px; color:#475569; font-size:14px;">Hello {{ $feedback->submitter_name ?: 'there' }},</p>
<p style="margin:0 0 20px; color:#475569; font-size:14px; line-height:1.6;">Thank you for reaching out. Here is our response regarding your submission:</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; border-radius:10px; margin:0 0 20px;">
<tr><td style="padding:14px 18px;">
<p style="margin:0; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:700;">Your original message</p>
<p style="margin:8px 0 0; color:#10203a; font-weight:700; font-size:14px;">{{ $feedback->subject }}</p>
<p style="margin:6px 0 0; color:#334155; font-size:13.5px; white-space:pre-line; line-height:1.6;">{{ $feedback->message }}</p>
</td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-left:3px solid #0b5ed7; margin:0 0 22px;">
<tr><td style="padding:2px 0 2px 16px; color:#1e293b; font-size:14px; line-height:1.6;">
{!! $replyHtml !!}
</td></tr>
</table>

<p style="margin:0; color:#94a3b8; font-size:12.5px; line-height:1.6;">
This message was sent in response to feedback submitted through the RANIAG public portal.
</p>
</x-mail-shell>
