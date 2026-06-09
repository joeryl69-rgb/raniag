# SMS Configuration Setup Guide

## Get Twilio Credentials

1. Visit: https://www.twilio.com/console
2. Sign in or create account
3. Copy these values:
   - Account SID: ACxxxxxxxxxxxxxxxxxxxxxxxxxx
   - Auth Token: (shown in dashboard)
   - Twilio Phone: +1234567890 (from phone numbers section)

## Update .env

Replace in your `.env` file:

```
SMS_PROVIDER=twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
```

## Update Agency Phone Numbers (Database)

SMS will be sent to agency phone numbers. Check that agencies have phones:

```sql
UPDATE agencies SET phone = '+63XXXXXXXXXX' WHERE id = 1;
```

Format: +63 (country code for Philippines) + 10-digit number

## Test SMS Integration

After updating .env, test by submitting a report:
1. Admin validates and assigns to agency
2. Check `sms_logs` table:
   ```sql
   SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 5;
   ```
3. Status should be "sent" (not "failed")
4. Provider response should contain Twilio's message SID

## Fallback (Placeholder)

If Twilio not configured:
- SMS marked as "sent" immediately
- Check sms_logs for placeholder indicators
- No actual SMS sent

## Troubleshooting

**Status: Failed**
- Check credentials in .env
- Verify phone number format (+63XXXXXXXXXX)
- Check Twilio account has SMS capability

**Log errors**
- Check `storage/logs/laravel.log`
- Look for Twilio error messages
