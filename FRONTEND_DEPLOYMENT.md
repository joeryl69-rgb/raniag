# Frontend Deployment Checklist

## Step 1: Verify Views Render

### Admin Views
- [ ] `http://localhost/admin/dashboard` — Shows metrics cards
- [ ] `http://localhost/admin/incidents` — Shows incident table
- [ ] Dashboar loads data from `/admin/dashboard.json`

### Agency Views
- [ ] `http://localhost/agency/dashboard` — Shows agency metrics
- [ ] `http://localhost/agency/incidents` — Shows assigned incidents
- [ ] Dashboard loads data from `/agency/dashboard.json`

### Public Views
- [ ] `http://localhost/report` — Shows report form
- [ ] `http://localhost/report/success` — Shows after submission
- [ ] `http://localhost/track` — Shows tracking form
- [ ] `http://localhost/track` (POST) — Shows incident details

## Step 2: Debug View Rendering Issues

If views don't display:

```bash
# Check view files exist
ls -la resources/views/admin/dashboard.blade.php
ls -la resources/views/agency/dashboard.blade.php

# Clear view cache
php artisan view:clear

# Check for syntax errors
php artisan tinker
>>> Blade::compileString('{{ $test }}')
```

## Step 3: Customize Views (Optional)

Current views use:
- Tailwind CSS for styling
- Inline JavaScript for API fetching
- Laravel Blade template syntax

To customize:
1. Edit: `resources/views/admin/dashboard.blade.php`
2. Edit: `resources/views/agency/dashboard.blade.php`
3. Clear cache: `php artisan view:clear`
4. Refresh browser

## Step 4: Deploy to Production

### Using Laravel's Built-in Server (Development)
```bash
php artisan serve
```

### Using Apache/Nginx (Production)
1. Point document root to: `public/`
2. Ensure `storage/` is writable
3. Set `.env APP_DEBUG=false`
4. Run: `php artisan config:cache`

### Using Docker
```dockerfile
FROM php:8.2-apache
COPY . /var/www/html
WORKDIR /var/www/html
RUN composer install
RUN chmod -R 755 storage/
RUN a2enmod rewrite
```

## Step 5: Performance Optimization

```bash
# Optimize Composer autoloader
composer dump-autoload --optimize

# Cache routes
php artisan route:cache

# Cache config
php artisan config:cache

# Pre-compile views
php artisan view:cache

# Cache database query results
php artisan cache:clear
php artisan cache:table (if using database cache)
```

## Step 6: Monitoring

Check logs for errors:
```bash
tail -f storage/logs/laravel.log
```

Monitor database:
```bash
# Recent activity logs
SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 20;

# Failed SMS attempts
SELECT * FROM sms_logs WHERE status = 'failed';

# Incident workflow status
SELECT tracking_number, status, updated_at FROM incidents ORDER BY updated_at DESC LIMIT 10;
```

## Progressive Web App (PWA) Enhancements

To make it PWA-ready:

1. Add manifest file: `public/manifest.json`
```json
{
  "name": "RANIAG",
  "short_name": "RANIAG",
  "display": "standalone",
  "start_url": "/",
  "background_color": "#ffffff",
  "theme_color": "#4CAF50"
}
```

2. Add service worker: `public/sw.js`
3. Reference in layout:
```html
<link rel="manifest" href="/manifest.json">
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
  }
</script>
```

## Frontend API Integration Reference

### Admin Dashboard JSON Format
```json
{
  "total_incidents": 5,
  "active_agencies": 3,
  "active_assignments": 2,
  "incident_status_breakdown": {
    "submitted": 1,
    "assigned": 1,
    "in_progress": 2,
    "resolved": 1
  },
  "recent_incidents": [
    {
      "id": 1,
      "tracking_number": "RAN-20260609-ABC1",
      "status": "in_progress",
      "incident_type": {"name": "Road Hazard"},
      "priority": "high",
      "reported_at": "2026-06-09T10:30:00Z"
    }
  ],
  "sms_stats": {
    "sent": 3,
    "failed": 0,
    "pending": 0
  }
}
```

### Agency Dashboard JSON Format
```json
{
  "agency_id": 1,
  "total_assigned_incidents": 5,
  "pending_resolutions": 2,
  "incident_status_breakdown": {
    "assigned": 1,
    "in_progress": 2,
    "pending_info": 0
  },
  "recent_status_updates": [
    {
      "incident_id": 1,
      "from_status": "assigned",
      "to_status": "in_progress",
      "comment": "Investigation started",
      "created_at": "2026-06-09T11:00:00Z"
    }
  ],
  "sms_alerts_this_week": 3
}
```

## Customization Examples

### Add Real-time Updates (WebSocket)

1. Install Laravel Echo Server
2. Add to view:
```javascript
Echo.channel('incidents')
  .listen('IncidentUpdated', (event) => {
    // Refresh dashboard on incident update
    location.reload();
  });
```

### Add Charts (Chart.js)

```html
<canvas id="statusChart"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch('/admin/dashboard.json')
  .then(r => r.json())
  .then(data => {
    new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: Object.keys(data.incident_status_breakdown),
        datasets: [{
          data: Object.values(data.incident_status_breakdown)
        }]
      }
    });
  });
</script>
```

## Support

- Check `IMPLEMENTATION_SUMMARY.md` for technical details
- Check `WORKFLOW_TESTING.md` for API endpoint reference
- Check `SMS_SETUP.md` for SMS integration guide
