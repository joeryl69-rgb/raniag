# RANIAG Quick Start Guide

## Prerequisites
- PHP 8.2+
- MySQL/SQLite database
- Composer
- (Optional) Twilio account for SMS

## 1️⃣ Initial Setup (Do Once)

```bash
cd c:/xampp/htdocs/raniag

# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed test data
php artisan db:seed
```

**Test Credentials After Seeding:**
- Admin: `admin@pamplona.gov.ph` / `password`
- Agency: `pnp@pamplona.gov.ph` / `password`

---

## 2️⃣ Start Development Server

```bash
php artisan serve
```

Server runs at: `http://localhost:8000`

---

## 3️⃣ Test Workflow (Complete End-to-End)

**Manual Testing:**

### A. Public Submits Report
1. Visit: http://localhost:8000/report
2. Fill form, submit
3. Copy tracking number from confirmation

### B. Public Tracks Report
1. Visit: http://localhost:8000/track
2. Enter tracking number
3. See "Submitted" status

### C. Admin Validates
1. Login: admin@pamplona.gov.ph / password
2. Visit: http://localhost:8000/admin/incidents
3. Click incident → Approve & assign to PNP
4. Status → "Assigned"

### D. Agency Accepts
1. Login: pnp@pamplona.gov.ph / password
2. Visit: http://localhost:8000/agency/incidents
3. Click → Accept Assignment
4. Status → "InProgress"

### E. Agency Updates Status
1. Same incident → Update Status → "InProgress"
2. Add comment → Submit
3. Status visible in public tracking

### F. Agency Submits Resolution
1. Same incident → Submit Resolution
2. Fill summary & actions
3. Status → "Resolved"

### G. Public Sees Resolution
1. Visit: http://localhost:8000/track
2. Enter same tracking number
3. See full history and "Resolved" status

---

## 4️⃣ Setup SMS (Twilio)

**Skip if not needed - uses placeholder SMS**

### A. Get Twilio Account
1. Sign up: https://www.twilio.com/console
2. Note:
   - Account SID
   - Auth Token
   - Phone Number (+1234567890 format)

### B. Configure .env
```
SMS_PROVIDER=twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_token_here
TWILIO_PHONE_NUMBER=+1234567890
```

### C. Update Agency Phone Numbers
```sql
-- In database:
UPDATE agencies SET phone = '+63XXXXXXXXXX' WHERE id = 1;
```

### D. Verify SMS Working
1. Submit report → Admin validates → Assigns
2. Check database:
```sql
SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 3;
```
3. Status should be "sent" (not "failed")

---

## 5️⃣ View Frontend

All views already configured to load JSON APIs.

**Admin Dashboard**
- URL: http://localhost:8000/admin/dashboard
- Loads: `/admin/dashboard.json`
- Shows: Total incidents, status breakdown, assignments, SMS stats

**Agency Dashboard**
- URL: http://localhost:8000/agency/dashboard
- Loads: `/agency/dashboard.json`
- Shows: Assigned incidents, pending resolutions, SMS alerts

---

## 📊 Dashboard API Endpoints

### Admin Only
```
GET  /admin/dashboard         - HTML dashboard
GET  /admin/dashboard.json    - JSON metrics
GET  /admin/incidents         - List all incidents
GET  /admin/incidents/{id}    - Single incident
POST /admin/incidents/{id}/validate - Approve/reject
```

### Agency Only
```
GET  /agency/dashboard        - HTML dashboard
GET  /agency/dashboard.json   - JSON metrics
GET  /agency/incidents        - Assigned incidents
GET  /agency/incidents/{id}   - Single incident
PATCH /agency/incidents/{id}/status - Update status
POST /agency/incidents/{id}/accept - Accept assignment
POST /agency/resolutions/{id} - Submit resolution
```

### Public (No Login)
```
GET  /report                  - Report form
POST /report                  - Submit report
GET  /track                   - Track form
POST /track                   - Lookup incident
```

---

## 🐛 Debugging

### Clear Caches
```bash
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Database Queries
```bash
php artisan tinker
>>> DB::enableQueryLog();
>>> DB::getQueryLog();
```

### Status Transitions
```sql
SELECT i.tracking_number, i.status, su.from_status, su.to_status, su.created_at
FROM incidents i
JOIN status_updates su ON i.id = su.incident_id
ORDER BY su.created_at DESC
LIMIT 20;
```

---

## 📁 Important Directories

```
resources/views/
├── admin/dashboard.blade.php        ← Admin dashboard
├── agency/dashboard.blade.php       ← Agency dashboard
└── public/
    ├── report/create.blade.php      ← Report form
    ├── report/success.blade.php     ← Report confirmation
    ├── track/index.blade.php        ← Track form
    └── track/show.blade.php         ← Track results

app/Services/
├── IncidentService.php              ← Core workflow
├── AssignmentService.php            ← Assignment logic
├── ResolutionService.php            ← Resolution logic
└── NotificationService.php          ← SMS logic

routes/
├── admin.php                        ← Admin endpoints
├── agency.php                       ← Agency endpoints
└── public.php                       ← Public endpoints
```

---

## 🚀 Production Deployment

```bash
# Optimize for production
composer dump-autoload --optimize
php artisan config:cache
php artisan route:cache

# Build deployment
git add .
git commit -m "Production deployment"
git push heroku main  # or your deployment target
```

---

## 📞 Support Resources

- **Workflow Testing**: See `WORKFLOW_TESTING.md`
- **SMS Setup**: See `SMS_SETUP.md`
- **Frontend**: See `FRONTEND_DEPLOYMENT.md`
- **Implementation**: See `IMPLEMENTATION_SUMMARY.md`

---

## ✅ Checklist

- [ ] Database migrated
- [ ] Test data seeded
- [ ] Dev server running
- [ ] Public workflow tested
- [ ] Admin login working
- [ ] Agency workflow tested
- [ ] SMS configured (optional)
- [ ] Dashboards displaying data
- [ ] All status transitions working
