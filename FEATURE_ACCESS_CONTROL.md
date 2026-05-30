# Feature: Location-Based Access Control

## Overview

Dual-access system where exam access rules differ based on **where** the user is accessing from:

| Access Location | Login Required | Subscription Check |
|-----------------|----------------|-------------------|
| Coaching Center (in-house) | No | No |
| Home/External | Yes | Yes |

---

## Implementation Status: COMPLETE

### Detection Method: IP Whitelist

- Store coaching center static IP addresses in database
- Admin can add/remove IPs from dashboard
- Middleware checks request IP against stored IPs
- If IP matches → Coaching Mode (no login required)
- If IP doesn't match → External Mode (login + subscription required)

---

## Files Created/Modified

### New Files

1. **Migration**: `database/migrations/2026_03_09_180000_create_coaching_ips_table.php`
   - Creates `coaching_ips` table with: id, ip_address, label, is_active, timestamps

2. **Model**: `app/Models/CoachingIp.php`
   - Eloquent model with helper methods: `isCoachingIp()`, `getActiveIps()`

3. **Factory**: `database/factories/CoachingIpFactory.php`
   - For testing purposes

4. **Service**: `app/Services/CoachingAccessService.php`
   - IP checking logic with caching
   - Methods: `isCoachingAccess()`, `getClientIp()`, `isWhitelistedIp()`, `clearCache()`

5. **Middleware**: `app/Http/Middleware/CoachingAccessMiddleware.php`
   - Allows access from coaching IPs without login
   - Requires login for external access

6. **Controller**: `app/Http/Controllers/CoachingIpController.php`
   - Full CRUD for coaching IPs
   - Includes `addCurrentIp()` for quick setup

7. **Vue Pages**:
   - `resources/js/pages/Settings/CoachingIps/Index.vue`
   - `resources/js/pages/Settings/CoachingIps/Create.vue`
   - `resources/js/pages/Settings/CoachingIps/Edit.vue`

### Modified Files

1. **bootstrap/app.php**: Added `coaching` middleware alias
2. **routes/web.php**: Wrapped exam routes with `coaching` middleware
3. **app/Http/Middleware/HandleInertiaRequests.php**: Shares `isCoachingMode` and `clientIp` with frontend
4. **app/Http/Middleware/CheckSubscription.php**: Bypasses subscription check for coaching mode
5. **app/Http/Controllers/ExamController.php**: Skips subscription checks in coaching mode for `register()` and `registerPartial()`
6. **resources/js/components/AppSidebar.vue**: Added "Coaching IPs" link

---

## Setup Instructions

### Step 1: Run Migration

```bash
php artisan migrate
```

### Step 2: Add Coaching Center IP

1. Go to **Dashboard** → **Coaching IPs** in the sidebar
2. Your current IP is displayed at the top
3. Click **"Add This IP"** to add your current IP
4. Or click **"Add IP Address"** to manually enter an IP

### Step 3: Test

**From Coaching IP (whitelisted):**
- Navigate to any exam page
- Should access without login

**From External IP (not whitelisted):**
- Navigate to any exam page
- Should redirect to login

---

## How It Works

```
Request → CoachingAccessMiddleware
              │
              ├── Get client IP (normalized for IPv6/IPv4)
              │
              ├── Check IP against coaching_ips table
              │
              ├── If IP matches (coaching):
              │   ├── Set is_coaching_mode = true on request
              │   └── Allow access (no login needed)
              │
              └── If IP doesn't match (external):
                  ├── User logged in? → Allow access
                  └── Not logged in? → Redirect to login

              ↓ (If allowed to continue)

         ExamController::register()
              │
              ├── Check is_coaching_mode from request
              │
              ├── If coaching mode:
              │   └── Skip subscription/limit checks
              │
              └── If external mode:
                  └── Validate subscription and limits
```

### IP Normalization

The system automatically normalizes IP addresses for consistent matching:
- `::1` (IPv6 localhost) → `127.0.0.1`
- `::ffff:192.168.1.1` (IPv4-mapped IPv6) → `192.168.1.1`

---

## Admin Features

### Coaching IPs Management Page (`/settings/coaching-ips`)

- View all configured coaching IPs
- See your current IP address
- Quick "Add This IP" button
- Search and filter (All/Active/Inactive)
- Enable/Disable individual IPs
- Edit or delete IPs

### Shared Data (Available in All Pages)

Via Inertia shared props:
- `isCoachingMode`: Boolean - true if accessing from coaching center
- `clientIp`: String - the user's current IP address

---

## Cache

- Coaching IPs are cached for 5 minutes for performance
- Cache is automatically cleared when:
  - Adding a new IP
  - Updating an IP
  - Deleting an IP
  - Toggling IP status

---

## Security Notes

- Only admin users can access the Coaching IPs management page
- IP validation ensures only valid IPv4/IPv6 addresses can be added
- IPs must be unique in the system

---

## Future Enhancements (Optional)

- [ ] IP range support (e.g., 192.168.1.0/24)
- [ ] Time-based access (coaching hours only)
- [ ] Coaching usage statistics
- [ ] Multiple coaching centers with separate IPs
