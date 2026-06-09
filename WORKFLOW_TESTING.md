# RANIAG Workflow Testing Guide

## Endpoint Reference

### Public API (No Auth)
- `GET /report` - Report form
- `POST /report` - Submit report
- `GET /track` - Track form
- `POST /track` - Look up incident

### Admin Workflow
- `GET /admin/incidents` - List all incidents
- `POST /admin/incidents/{id}/validate` - Approve/reject report
- `GET /admin/incidents/{id}/assignments` - View assignments
- `POST /admin/assignments` - Create assignment
- `PATCH /admin/assignments/{id}` - Update assignment
- `POST /admin/assignments/{id}/complete` - Complete assignment

### Agency Workflow
- `GET /agency/incidents` - List assigned incidents
- `PATCH /agency/incidents/{id}/status` - Update investigation status
- `POST /agency/incidents/{id}/accept` - Accept assignment
- `POST /agency/resolutions/{id}` - Submit resolution

### Dashboards
- `GET /admin/dashboard` - Admin dashboard HTML
- `GET /admin/dashboard.json` - Admin metrics API
- `GET /agency/dashboard` - Agency dashboard HTML
- `GET /agency/dashboard.json` - Agency metrics API

## Complete Workflow Test

### Step 1: Public Submits Report
```bash
curl -X POST http://localhost/report \
  -H "Content-Type: application/json" \
  -d '{
    "incident_type_id": 1,
    "description": "Large pothole on Main Street",
    "location_address": "Main Street, Pamplona",
    "barangay": "Santa Cruz",
    "latitude": 18.4720,
    "longitude": 121.3250,
    "is_anonymous": true,
    "priority": "high"
  }'
```
**Response**: `tracking_number: RAN-20260609-XXXX`, status: `submitted`

### Step 2: Admin Validates Report
```bash
curl -X POST http://localhost/admin/incidents/1/validate \
  -H "Content-Type: application/json" \
  -H "Cookie: LARAVEL_SESSION=..." \
  -d '{
    "action": "approve",
    "assigned_agency_id": 1,
    "notes": "Route to PNP for investigation"
  }'
```
**Response**: status transitions `submitted` → `received` → `assigned`, SMS sent to agency

### Step 3: Agency Accepts Assignment
```bash
curl -X POST http://localhost/agency/incidents/1/accept \
  -H "Content-Type: application/json" \
  -H "Cookie: LARAVEL_SESSION=..." \
  -d '{}'
```
**Response**: status transitions `assigned` → `in_progress`, assignment acknowledged

### Step 4: Agency Updates Status
```bash
curl -X PATCH http://localhost/agency/incidents/1/status \
  -H "Content-Type: application/json" \
  -H "Cookie: LARAVEL_SESSION=..." \
  -d '{
    "status": "in_progress",
    "comment": "Investigation underway"
  }'
```
**Response**: status updated, visible to public

### Step 5: Agency Submits Resolution
```bash
curl -X POST http://localhost/agency/resolutions/1 \
  -H "Content-Type: application/json" \
  -H "Cookie: LARAVEL_SESSION=..." \
  -d '{
    "summary": "Pothole repaired",
    "actions_taken": "Filled pothole with asphalt, compacted"
  }'
```
**Response**: status transitions `in_progress` → `resolved`, SMS to admin, public notification created

### Step 6: Public Tracks Report
```bash
curl -X POST http://localhost/track \
  -H "Content-Type: application/json" \
  -d '{"tracking_number": "RAN-20260609-XXXX"}'
```
**Response**: Full incident with status history visible to public

## Database Queries for Verification

### Verify status transitions
```sql
SELECT i.tracking_number, i.status, su.from_status, su.to_status, su.created_at
FROM incidents i
JOIN status_updates su ON i.id = su.incident_id
WHERE i.tracking_number = 'RAN-20260609-XXXX'
ORDER BY su.created_at;
```

### Verify SMS logs
```sql
SELECT recipient_phone, message, status, created_at
FROM sms_logs
WHERE incident_id = 1
ORDER BY created_at DESC;
```

### Verify assignments
```sql
SELECT a.id, a.incident_id, ag.name, a.is_active, a.assigned_at, a.completed_at
FROM assignments a
JOIN agencies ag ON a.agency_id = ag.id
WHERE a.incident_id = 1;
```

### Verify evidence prioritization
```sql
SELECT file_path, priority, is_gps_capture, created_at
FROM evidence
WHERE incident_id = 1
ORDER BY priority DESC, is_gps_capture DESC;
```

## Manual UI Testing Checklist

- [ ] Public can submit report with/without anonymous flag
- [ ] Tracking number is generated and returned
- [ ] Admin sees submitted report in dashboard
- [ ] Admin can validate and assign to agency
- [ ] Agency receives SMS notification
- [ ] Agency dashboard shows assigned incident
- [ ] Agency can accept assignment
- [ ] Agency status changes reflected publicly
- [ ] Agency can submit resolution
- [ ] Admin receives SMS notification of resolution
- [ ] Public can track and see full status history
- [ ] GPS camera photos appear in evidence with priority flag
- [ ] Dashboard metrics update in real-time

## SMS Provider Integration (Placeholder)

Current implementation uses placeholder SMS dispatch. To integrate with Twilio:

1. Install: `composer require twilio/sdk`
2. Update `.env`:
   ```
   SMS_PROVIDER=twilio
   TWILIO_ACCOUNT_SID=your_sid
   TWILIO_AUTH_TOKEN=your_token
   TWILIO_PHONE_NUMBER=+1234567890
   ```
3. Update `NotificationService::dispatchSms()`:
   ```php
   $twilio = new Twilio\Rest\Client(
       config('services.twilio.account_sid'),
       config('services.twilio.auth_token')
   );
   $twilio->messages->create($smsLog->recipient_phone, [
       'from' => config('services.twilio.phone_number'),
       'body' => $smsLog->message,
   ]);
   ```

## Notes

- All status transitions are validated by `IncidentStatus::availableTransitions()`
- Activities logged to `activity_logs` table for audit trail
- Status changes recorded in `status_updates` table
- SMS dispatch is idempotent (can retry safely)
- Evidence prioritized by GPS capture flag
- Public visibility controlled by `StatusUpdate.is_public` flag
