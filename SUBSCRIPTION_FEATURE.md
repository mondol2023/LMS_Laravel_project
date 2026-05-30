# Subscription Module - IELTS Mock Test Platform

## Overview

The subscription module allows admins to create packages with different access levels and pricing, and assign users to these packages. Users get limited or unlimited access to various exam types and practice modules based on their subscription package.

### Key Clarifications

1. **Partial Mock Test Parts - Separate Limits**
   - Each part (Reading, Writing, Listening, Speaking) has its **own separate limit**
   - Example package limits:
     * Reading parts: 10
     * Writing parts: 8
     * Listening parts: 12
     * Speaking parts: 6
   - User can take up to the limit for each specific part independently

2. **Practice Module Access**
   - **Reading Practice**: Checkbox to enable/disable → Always **unlimited** when enabled
   - **Listening Practice**: Checkbox to enable/disable → Always **unlimited** when enabled
   - **Writing Practice**: Checkbox to enable/disable + Number limit field
   - **Speaking Practice**: Checkbox to enable/disable + Number limit field

3. **Full Mock Tests**
   - Counted as complete tests (all 4 parts together)
   - Each full test = 1 count toward limit
   - Example: If limit is 10, user can take 10 complete full mock tests

4. **Benefit of Separate Part Limits**
   - Allows admins to balance difficulty and value
   - Example: Can give more listening attempts than speaking
   - User can focus on weaker areas without consuming overall limit

---

## Core Concepts

### 1. Subscription Packages
Packages are predefined plans created by admins with specific:
- Pricing and discounts
- Duration (validity period)
- Access limits for different exam types and practice modules

### 2. User Subscriptions
When a user is assigned a package:
- They get access based on package limits
- Usage is tracked against their limits
- Admins can upgrade/downgrade their package
- Expiry is automatically managed

---

## Database Schema

### `subscription_packages` Table

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Package name (e.g., "Basic", "Premium", "Ultimate") |
| `duration` | integer | Duration in days |
| `price` | decimal(10,2) | Original price in BDT |
| `discount` | decimal(10,2) | Discount amount in BDT |
| `discount_type` | enum | 'flat' or 'percent' |
| `discount_till` | timestamp | Discount expiry date (nullable) |
| `is_active` | boolean | Package status |
| **Mock Test Limits** |
| `full_mock_test_limit` | integer | Number of full mock tests allowed (nullable = unlimited) |
| **Partial Mock Test - Individual Part Limits** |
| `partial_reading_limit` | integer | Number of reading parts in partial tests (nullable = unlimited) |
| `partial_writing_limit` | integer | Number of writing parts in partial tests (nullable = unlimited) |
| `partial_listening_limit` | integer | Number of listening parts in partial tests (nullable = unlimited) |
| `partial_speaking_limit` | integer | Number of speaking parts in partial tests (nullable = unlimited) |
| **Practice Module Access** |
| `practice_reading_enabled` | boolean | Enable reading practice (always unlimited if enabled) |
| `practice_listening_enabled` | boolean | Enable listening practice (always unlimited if enabled) |
| `practice_writing_enabled` | boolean | Enable writing practice |
| `practice_writing_limit` | integer | Number of writing practice attempts (nullable = unlimited if enabled) |
| `practice_speaking_enabled` | boolean | Enable speaking practice |
| `practice_speaking_limit` | integer | Number of speaking practice attempts (nullable = unlimited if enabled) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `user_subscriptions` Table

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | Foreign key to users table |
| `package_id` | bigint | Foreign key to subscription_packages table |
| `subscribed_at` | timestamp | When subscription started |
| `expires_at` | timestamp | When subscription expires |
| `is_active` | boolean | Current subscription status |
| **Usage Tracking** |
| `full_mock_tests_used` | integer | Count of full mock tests taken |
| **Partial Mock Test - Part Usage** |
| `partial_reading_used` | integer | Count of reading parts taken |
| `partial_writing_used` | integer | Count of writing parts taken |
| `partial_listening_used` | integer | Count of listening parts taken |
| `partial_speaking_used` | integer | Count of speaking parts taken |
| **Practice Module Usage** |
| `practice_writing_used` | integer | Count of writing practices used |
| `practice_speaking_used` | integer | Count of speaking practices used |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `subscription_history` Table

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | Foreign key to users |
| `old_package_id` | bigint | Previous package (nullable) |
| `new_package_id` | bigint | New package |
| `action` | enum | 'assigned', 'upgraded', 'downgraded', 'renewed', 'cancelled' |
| `performed_by` | bigint | Admin user ID who made the change |
| `notes` | text | Optional notes (nullable) |
| `created_at` | timestamp | |

---

## Features Breakdown

### For Admins

#### 1. Package Management

**Create Package**
```
- Package name
- Duration (days)
- Price (BDT)
- Discount amount/percentage
- Discount valid till (optional)
- Mock test limits:
  ✓ Full mock tests (number or unlimited)
  ✓ Partial mock tests - separate limits for each part:
    → Reading parts: (number or unlimited)
    → Writing parts: (number or unlimited)
    → Listening parts: (number or unlimited)
    → Speaking parts: (number or unlimited)
- Practice modules:
  ✓ Reading practice [✓ Enable] (unlimited when enabled)
  ✓ Listening practice [✓ Enable] (unlimited when enabled)
  ✓ Writing practice [✓ Enable] + Limit (number or unlimited)
  ✓ Speaking practice [✓ Enable] + Limit (number or unlimited)
- Active/Inactive status
```

**Edit Package**
- Update any package details
- Changes don't affect existing subscriptions retroactively
- Option to apply changes to active subscriptions

**Delete Package**
- Can only delete if no active subscriptions
- Or mark as inactive to hide from new assignments

**View Packages**
- List all packages
- Filter: Active/Inactive
- Sort by: Name, Price, Created date
- Show statistics: Active users count per package

#### 2. User Subscription Management

**Assign Package to New User**
```
When creating a new user:
1. Select a package from dropdown
2. Subscription starts immediately
3. Expiry calculated based on package duration
```

**Assign Package to Existing User**
```
From user profile or users list:
1. Select user
2. Choose package
3. Subscription replaces current one (if any)
4. Start date: Immediate or custom
5. Logs action in subscription_history
```

**Upgrade Package**
```
- User gets better package
- Remaining days from old package can be:
  a) Forfeited (simple)
  b) Prorated and applied to new package (complex)
  c) Extended to new expiry (bonus days)
- Usage counters: Reset or carry forward
- Logs action in history
```

**Downgrade Package**
```
- User gets lower-tier package
- Can take effect:
  a) Immediately
  b) At current subscription end
- Usage counters adjusted if needed
- Logs action in history
```

**Renew Package**
```
- Extend current package
- Option to change package during renewal
- Reset usage counters
- New expiry = current expiry + duration
```

**Cancel Subscription**
```
- Can take effect:
  a) Immediately
  b) At subscription end
- Mark as inactive
- User loses access based on cancellation timing
```

**View User Subscriptions**
```
From user profile:
- Current package
- Subscription start/end dates
- Usage statistics:
  Mock Tests:
  ✓ Full mock tests: X used / Y limit

  Partial Mock Tests (by part):
  ✓ Reading parts: X used / Y limit
  ✓ Writing parts: X used / Y limit
  ✓ Listening parts: X used / Y limit
  ✓ Speaking parts: X used / Y limit

  Practice Modules:
  ✓ Writing practice: X used / Y limit (or "Not enabled")
  ✓ Speaking practice: X used / Y limit (or "Not enabled")
  ✓ Reading practice: X used / Unlimited (or "Not enabled")
  ✓ Listening practice: X used / Unlimited (or "Not enabled")
- Subscription history (all past packages)
```

#### 3. Subscription Reports

**Dashboard Statistics**
- Total active subscriptions
- Revenue (monthly, yearly)
- Most popular package
- Expiring subscriptions (next 7/30 days)
- Package usage analytics

**Subscription List**
- All subscriptions with filters:
  - Package type
  - Active/Expired/Expiring soon
  - Date range
- Export to CSV/Excel

---

### For Users

#### 1. Dashboard View
```
My Subscription Card:
- Package name
- Status (Active / Expired)
- Expiry date
- Progress bars showing usage:
  ✓ Full Mock Tests: [████░░░░] 4/10 remaining

  Partial Mock Tests (by part):
  ✓ Reading Parts: [██████░░] 6/10 remaining
  ✓ Writing Parts: [███░░░░░] 3/8 remaining
  ✓ Listening Parts: [████████] 12/12 remaining
  ✓ Speaking Parts: [██░░░░░░] 2/6 remaining

  Practice Modules:
  ✓ Writing Practice: [███░░░░░] 3/15 remaining (if enabled)
  ✓ Speaking Practice: [█████░░░] 5/10 remaining (if enabled)
  ✓ Reading Practice: Unlimited (if enabled)
  ✓ Listening Practice: Unlimited (if enabled)
```

#### 2. Access Control
```
When attempting to access:

Full Mock Test:
- Check: has active subscription?
- Check: full_mock_tests_used < limit?
- If yes: Allow + increment counter
- If no: Show upgrade message

Partial Mock Test - Reading Part:
- Check: has active subscription?
- Check: partial_reading_used < partial_reading_limit?
- If yes: Allow + increment reading counter by 1
- If no: Show "Reading parts limit reached" message

Partial Mock Test - Writing Part:
- Check: has active subscription?
- Check: partial_writing_used < partial_writing_limit?
- If yes: Allow + increment writing counter by 1
- If no: Show "Writing parts limit reached" message

Partial Mock Test - Listening Part:
- Check: has active subscription?
- Check: partial_listening_used < partial_listening_limit?
- If yes: Allow + increment listening counter by 1
- If no: Show "Listening parts limit reached" message

Partial Mock Test - Speaking Part:
- Check: has active subscription?
- Check: partial_speaking_used < partial_speaking_limit?
- If yes: Allow + increment speaking counter by 1
- If no: Show "Speaking parts limit reached" message

Writing Practice:
- Check: practice_writing_enabled?
- Check: practice_writing_used < limit?
- If yes: Allow + increment counter
- If no: Show upgrade/enable message

Speaking Practice:
- Check: practice_speaking_enabled?
- Check: practice_speaking_used < limit?
- If yes: Allow + increment counter
- If no: Show upgrade/enable message

Reading Practice:
- Check: practice_reading_enabled?
- If yes: Allow (unlimited)
- If no: Show "Not included in your package" message

Listening Practice:
- Check: practice_listening_enabled?
- If yes: Allow (unlimited)
- If no: Show "Not included in your package" message
```

#### 3. Notifications
```
- 7 days before expiry: "Your subscription expires soon"
- On expiry: "Your subscription has expired"
- When limit reached: "You've used all your [X] attempts"
- After upgrade: "Your subscription has been upgraded"
```

---

## User Flows

### Admin Flow: Creating a Package

```
1. Navigate to Subscriptions > Packages
2. Click "Create Package"
3. Fill form:
   - Name: "Premium Plan"
   - Duration: 90 days
   - Price: 5000 BDT
   - Discount: 500 BDT or 10%
   - Discount valid till: 2026-12-31
   - Mock Test Access:
     * Full mock tests: 15
     * Partial mock tests (separate limits):
       - Reading parts: 20
       - Writing parts: 15
       - Listening parts: 25
       - Speaking parts: 15
   - Practice Modules:
     * [✓] Reading practice (Unlimited when enabled)
     * [✓] Listening practice (Unlimited when enabled)
     * [✓] Writing practice → Limit: 50
     * [✓] Speaking practice → Limit: 50
4. Save
5. Package appears in list
```

### Admin Flow: Assigning User to Package

```
Scenario A: New User
1. Navigate to Students > Create
2. Fill user details
3. Select package: "Premium Plan"
4. Submit
5. User created with active subscription

Scenario B: Existing User
1. Navigate to Students > View User
2. Click "Manage Subscription"
3. Select package: "Premium Plan"
4. Confirm start date
5. Submit
6. Subscription activated
7. History logged
```

### Admin Flow: Upgrading User Package

```
1. Navigate to Students > View User
2. Current package: "Basic Plan"
3. Click "Upgrade Subscription"
4. Select new package: "Premium Plan"
5. Choose upgrade option:
   - Start immediately
   - Adjust expiry date
   - Reset usage counters
6. Add optional notes
7. Submit
8. User gets upgraded access
9. History logged
```

### User Flow: Taking Full Mock Test

```
1. User logs in
2. Dashboard shows: "Full Mock Tests: 8/15 remaining"
3. Clicks "Take Full Mock Test"
4. System checks:
   - Subscription active? ✓
   - Limit available? ✓ (8 < 15)
5. Starts exam
6. On submission:
   - Counter incremented: 9/15
   - Result saved
7. Dashboard updates automatically
```

### User Flow: Taking Partial Mock Test Part

```
1. User logs in
2. Dashboard shows:
   - Reading Parts: 6/10 remaining
   - Writing Parts: 3/8 remaining
   - Listening Parts: 12/12 remaining
   - Speaking Parts: 2/6 remaining

3. Clicks "Take Partial Mock Test" → Selects "Listening"
4. System checks:
   - Subscription active? ✓
   - Listening parts limit available? ✓ (0/12 < 12)
5. Starts listening part only
6. On submission:
   - Listening parts counter incremented by 1: 1/12
   - Result saved
7. Dashboard updates automatically

Later same day:
8. User takes "Writing" part
9. Writing parts counter incremented by 1: 4/8
10. User can still take:
    - 6 more Reading parts
    - 4 more Writing parts
    - 11 more Listening parts
    - 2 more Speaking parts
```

### User Flow: Limit Reached

```
1. User attempts writing practice
2. Current usage: 30/30
3. System shows modal:
   "You've reached your Writing Practice limit!

   Upgrade your plan to continue practicing.

   [View Plans] [Contact Admin]"
4. Cannot proceed without upgrade
```

### Example Scenario: Partial Mock Test Parts Usage

```
User has "Basic Plan" with separate limits for each part:
- Reading: 10 parts
- Writing: 8 parts
- Listening: 12 parts
- Speaking: 6 parts

Week 1:
- Takes Reading part → Reading: 1/10
- Takes Writing part → Writing: 1/8
- Takes Listening part → Listening: 1/12
- Takes Speaking part → Speaking: 1/6

Week 2:
- Takes Writing part twice → Writing: 3/8
- Takes Listening part three times → Listening: 4/12

Week 3:
- Takes Reading five times → Reading: 6/10
- Takes Speaking twice → Speaking: 3/6
- Tries Writing part again → Writing: 4/8

Current status:
- Reading: 6/10 remaining (4 left)
- Writing: 4/8 remaining (4 left)
- Listening: 4/12 remaining (8 left)
- Speaking: 3/6 remaining (3 left)

Week 4:
- Takes all 6 remaining Speaking parts → Speaking: 6/6 MAXED OUT
- Next Speaking attempt: "You've reached your Speaking parts limit!"
- But can still take: Reading (4), Writing (4), Listening (8)
```

---

## Technical Implementation Notes

### 1. Middleware for Access Control

```php
// CheckSubscriptionAccess middleware
- Verify user has active subscription
- Check expiry date
- Verify specific resource limits
- Redirect if no access
```

### 2. Helper Functions

```php
// Subscription helper methods
hasActiveSubscription(): bool
getRemainingFullMockTests(): int

// Partial mock tests - separate methods for each part
getRemainingPartialReading(): int
getRemainingPartialWriting(): int
getRemainingPartialListening(): int
getRemainingPartialSpeaking(): int

// Practice modules
isPracticeModuleEnabled(string $module): bool // reading, listening, writing, speaking
getRemainingWritingPractice(): ?int // null if unlimited or not enabled
getRemainingActiveSpeakingPractice(): ?int // null if unlimited or not enabled

// General
canAccessResource(string $type, ?string $part = null): bool
incrementUsage(string $type, ?string $part = null): void // increments by 1
isSubscriptionExpired(): bool
```

### 3. Automated Tasks (Scheduled Jobs)

```php
// Daily cron jobs
- Check and expire subscriptions
- Send expiry notifications (7 days, 3 days, 1 day)
- Generate usage reports
- Clean up old subscription history
```

### 4. Events & Listeners

```php
SubscriptionCreated
SubscriptionUpgraded
SubscriptionDowngraded
SubscriptionExpired
SubscriptionRenewed
LimitReached
```

---

## Package Examples

### Free Plan
```
Name: Free Trial
Duration: 7 days
Price: 0 BDT
Mock Tests:
- Full mock tests: 1
- Partial mock tests (by part):
  * Reading parts: 2
  * Writing parts: 1
  * Listening parts: 2
  * Speaking parts: 1
Practice Modules:
- Reading practice: ✓ Enabled (Unlimited)
- Listening practice: ✓ Enabled (Unlimited)
- Writing practice: ✓ Enabled (Limit: 3)
- Speaking practice: ✓ Enabled (Limit: 3)
```

### Basic Plan
```
Name: Basic
Duration: 30 days
Price: 1500 BDT
Discount: 200 BDT
Mock Tests:
- Full mock tests: 5
- Partial mock tests (by part):
  * Reading parts: 10
  * Writing parts: 8
  * Listening parts: 12
  * Speaking parts: 6
Practice Modules:
- Reading practice: ✓ Enabled (Unlimited)
- Listening practice: ✓ Enabled (Unlimited)
- Writing practice: ✓ Enabled (Limit: 15)
- Speaking practice: ✓ Enabled (Limit: 15)
```

### Premium Plan
```
Name: Premium
Duration: 90 days
Price: 5000 BDT
Discount: 10%
Mock Tests:
- Full mock tests: 15
- Partial mock tests (by part):
  * Reading parts: 20
  * Writing parts: 15
  * Listening parts: 25
  * Speaking parts: 15
Practice Modules:
- Reading practice: ✓ Enabled (Unlimited)
- Listening practice: ✓ Enabled (Unlimited)
- Writing practice: ✓ Enabled (Limit: 50)
- Speaking practice: ✓ Enabled (Limit: 50)
```

### Ultimate Plan
```
Name: Ultimate
Duration: 365 days
Price: 15000 BDT
Discount: 3000 BDT
Mock Tests:
- Full mock tests: Unlimited
- Partial mock tests (by part):
  * Reading parts: Unlimited
  * Writing parts: Unlimited
  * Listening parts: Unlimited
  * Speaking parts: Unlimited
Practice Modules:
- Reading practice: ✓ Enabled (Unlimited)
- Listening practice: ✓ Enabled (Unlimited)
- Writing practice: ✓ Enabled (Unlimited)
- Speaking practice: ✓ Enabled (Unlimited)
```

---

## UI Components Needed

### Admin Panel
1. **Packages Page** (`/admin/subscriptions/packages`)
   - List view with create button
   - Edit/Delete actions
   - Active/Inactive toggle

2. **Create/Edit Package Form** (`/admin/subscriptions/packages/create`)
   - Basic info: Name, duration, price, discount
   - Mock test limits section:
     * Full mock tests: [Number input] (or checkbox for unlimited)
     * Partial mock tests - separate inputs for each part:
       - Reading parts: [Number input] (or checkbox for unlimited)
       - Writing parts: [Number input] (or checkbox for unlimited)
       - Listening parts: [Number input] (or checkbox for unlimited)
       - Speaking parts: [Number input] (or checkbox for unlimited)
   - Practice modules section:
     * Reading: [✓] Enable (always unlimited if checked)
     * Listening: [✓] Enable (always unlimited if checked)
     * Writing: [✓] Enable + [Number input] Limit
     * Speaking: [✓] Enable + [Number input] Limit
   - Validation and error display
   - Preview: Calculated price with discount

3. **User Subscription Management** (`/admin/students/{id}/subscription`)
   - Current subscription card
   - Assign/Change package dropdown
   - Upgrade/Downgrade buttons
   - Usage statistics
   - History timeline

4. **Subscription Reports** (`/admin/subscriptions/reports`)
   - Statistics cards
   - Charts (subscriptions over time, revenue)
   - Data table with filters
   - Export functionality

### User Panel
1. **My Subscription** (`/dashboard` or `/my-subscription`)
   - Package details card
   - Usage progress bars:
     * Full mock tests
     * Partial mock tests - 4 separate progress bars (Reading, Writing, Listening, Speaking)
     * Practice modules - conditional display based on enabled modules
   - Expiry countdown
   - Upgrade button (if applicable)

2. **Access Restriction Modals**
   - Limit reached message
   - Subscription expired message
   - Upgrade CTA

---

## Business Logic Rules

### Package Assignment
1. User can only have ONE active subscription at a time
2. New assignment cancels previous subscription
3. Start date can be immediate or future
4. Expiry calculated as: start_date + duration (days)

### Usage Tracking
1. Counters increment AFTER successful completion
2. Failed attempts don't count
3. Practice sessions count when submitted
4. Unlimited = NULL in database
5. Partial mock test parts: Each part has its own separate counter and limit
   - Taking Reading part: increments partial_reading_used only
   - Taking Writing part: increments partial_writing_used only
   - Taking Listening part: increments partial_listening_used only
   - Taking Speaking part: increments partial_speaking_used only
6. Practice modules: Only track usage if module is enabled in package

### Upgrades
1. Immediate effect by default
2. Old subscription marked inactive
3. Usage counters can reset or carry forward
4. New expiry = current date + new package duration

### Downgrades
1. Can be immediate or scheduled
2. If scheduled, takes effect at current subscription end
3. Usage counters adjusted if exceed new limits
4. Warning shown if user will lose access

### Renewals
1. Extends current package
2. Resets usage counters
3. New expiry = old expiry + duration (if extending)
4. Or new expiry = today + duration (if expired)

### Expiry
1. Auto-check daily via cron
2. Grace period: 0 days (strict)
3. Access immediately revoked on expiry
4. Can renew expired subscription

---

## Validation Rules

### Package Creation
- Name: required, unique, max 255 chars
- Duration: required, integer, min 1 day
- Price: required, decimal, min 0
- Discount: optional, decimal, min 0, max price
- Discount type: required if discount set
- Discount till: optional, date, after today
- Full mock test limit: optional, integer, min 1
- Partial mock test limits (separate for each part):
  * Reading parts: optional, integer, min 1
  * Writing parts: optional, integer, min 1
  * Listening parts: optional, integer, min 1
  * Speaking parts: optional, integer, min 1
- Practice module enabled: boolean (checkbox)
- Practice module limits: optional, integer, min 1 (if enabled)
  * Reading/Listening: no limit field (always unlimited if enabled)
  * Writing/Speaking: required if enabled, integer, min 1

### User Assignment
- User: required, exists in users table
- Package: required, exists, is_active = true
- Start date: optional, date, after or equal today
- Cannot assign if user has active subscription without force flag

---

## Edge Cases & Solutions

### User already has active subscription
**Solution:** Show confirmation modal: "User already has [Package A]. Replace with [Package B]?"

### Package deleted but users still subscribed
**Solution:** Soft delete packages. Mark as inactive but keep in DB.

### User reaches limit mid-session
**Solution:** Allow completion of started session, block new sessions.

### Admin changes package limits
**Solution:** Changes don't affect active subscriptions. Only new assignments get new limits.

### Subscription expires during exam
**Solution:** Allow completion of started exam. Block new exams.

### User has unlimited limit (NULL)
**Solution:** Never increment counter for unlimited resources.

### Practice module disabled in package
**Solution:** Show message: "This practice module is not included in your package. Please upgrade to access [Module Name] practice."

### User tries partial test but all parts disabled
**Solution:** Should not happen - validate at package creation that at least one resource is available.

### Prorated calculations for upgrades
**Solution:** (Optional feature)
- Calculate remaining value of old package
- Apply as credit to new package
- Extend expiry proportionally

---

## Future Enhancements

### Phase 2 Features
1. **Self-service Subscription**
   - Users can purchase packages directly
   - Payment gateway integration
   - Auto-activation after payment

2. **Discount Codes**
   - Separate from package discounts
   - Can apply coupon code during purchase
   - Combined with package discounts

3. **Family/Group Plans**
   - One subscription, multiple users
   - Shared limits or individual limits

4. **Referral System**
   - Refer friends, get bonus days/limits
   - Track referrals in subscription

5. **Auto-renewal**
   - Recurring payments
   - Auto-extend subscription
   - Email reminder before renewal

6. **Usage Analytics**
   - Detailed graphs of user activity
   - Compare usage across packages
   - Predict churn

7. **Package Recommendations**
   - AI suggests package based on usage pattern
   - Upgrade prompts when limits frequently hit

---

## Migration Strategy

### Existing Users
1. Create default "Legacy" package with unlimited access
2. Auto-assign all existing users to this package
3. Gradually migrate users to new packages
4. Sunset legacy package after migration

### Testing
1. Create test packages with low limits
2. Test all scenarios on staging
3. Verify usage tracking accuracy
4. Test expiry automation
5. Test upgrade/downgrade flows

---

## Questions to Clarify

1. **Package Duration**
   - Should we support monthly/yearly shortcuts or just days?
   - Should subscriptions auto-renew or require manual renewal?

2. **Partial Mock Tests** ✅ CLARIFIED
   - Each part (Reading, Writing, Listening, Speaking) has its own separate limit
   - Limits are tracked independently
   - User can max out one part while still having access to others

3. **Pricing & Discounts**
   - Can packages have both percentage and flat discounts?
   - Should discounts stack with coupon codes?

4. **Upgrade/Downgrade Logic**
   - Should unused time/credits be carried forward?
   - Should we allow prorated refunds/credits?

5. **Admin Assignment**
   - Can admins override limits for specific users?
   - Should admins be able to give "bonus" access without changing package?

6. **Access Control**
   - What happens to in-progress exams when subscription expires?
   - Should there be a grace period after expiry?

7. **Writing & Speaking Practice**
   - Are these AI-evaluated practices or manual?
   - Does one practice session = one limit usage?

---

## Development Checklist

### Backend
- [ ] Create migrations for all tables
- [ ] Create models: SubscriptionPackage, UserSubscription, SubscriptionHistory
- [ ] Create form requests for validation
- [ ] Create controllers: PackageController, SubscriptionController
- [ ] Create middleware: CheckSubscription
- [ ] Create helper trait: HasSubscription
- [ ] Create scheduled job: ExpireSubscriptions
- [ ] Create events & listeners
- [ ] Add routes (admin & API)
- [ ] Write unit tests
- [ ] Write feature tests

### Frontend
- [ ] Admin: Packages list page
- [ ] Admin: Create/edit package form
- [ ] Admin: User subscription management
- [ ] Admin: Subscription reports
- [ ] User: My subscription dashboard
- [ ] User: Limit reached modals
- [ ] User: Expiry notifications
- [ ] Global: Access control on all resources
- [ ] Global: Usage counters in navigation

### Testing
- [ ] Test package CRUD
- [ ] Test user assignment
- [ ] Test upgrade flow
- [ ] Test downgrade flow
- [ ] Test usage tracking
- [ ] Test expiry automation
- [ ] Test access control
- [ ] Test edge cases

---

## Summary

This subscription module provides complete control over user access to IELTS mock tests and practice modules. Admins can create flexible packages, assign users, track usage, and manage subscriptions throughout their lifecycle. Users get clear visibility into their subscription status and usage limits.

### Final Structure Overview

**Mock Tests:**
1. Full Mock Tests: Single counter (e.g., 15 full tests)
2. Partial Mock Tests: **4 separate counters** for each part
   - Reading parts: X limit
   - Writing parts: Y limit
   - Listening parts: Z limit
   - Speaking parts: W limit

**Practice Modules:**
1. Reading: Enable/Disable checkbox → Unlimited when enabled
2. Listening: Enable/Disable checkbox → Unlimited when enabled
3. Writing: Enable/Disable checkbox + Number limit
4. Speaking: Enable/Disable checkbox + Number limit

**Key Database Fields (8 limits total per package):**
- `full_mock_test_limit`
- `partial_reading_limit`
- `partial_writing_limit`
- `partial_listening_limit`
- `partial_speaking_limit`
- `practice_reading_enabled` (no limit field)
- `practice_listening_enabled` (no limit field)
- `practice_writing_enabled` + `practice_writing_limit`
- `practice_speaking_enabled` + `practice_speaking_limit`

The system is designed to be:
- **Flexible**: Supports various package configurations with granular control
- **Scalable**: Can handle many packages and users
- **Transparent**: Clear limits and usage tracking for each part
- **Automated**: Auto-expiry, notifications, usage tracking
- **Auditable**: Complete subscription history
- **Granular**: Separate limits allow precise control over user access

Next steps: Clarify remaining open questions, finalize database schema, begin implementation.

---

## Developer Implementation Guide

### Using the Subscription Middleware

The `CheckSubscription` middleware is available for protecting routes that require subscription access. It accepts a resource type parameter.

**Route Protection Examples:**

```php
// Full mock test route
Route::get('/exam/{uuid}/start', [ExamController::class, 'start'])
    ->middleware(['auth', 'subscription:full_mock_test']);

// Partial test routes - different for each part
Route::get('/exam/{uuid}/reading', [ExamController::class, 'reading'])
    ->middleware(['auth', 'subscription:partial_reading']);

Route::get('/exam/{uuid}/writing', [ExamController::class, 'writing'])
    ->middleware(['auth', 'subscription:partial_writing']);

Route::get('/exam/{uuid}/listening', [ExamController::class, 'listening'])
    ->middleware(['auth', 'subscription:partial_listening']);

Route::get('/exam/{uuid}/speaking', [ExamController::class, 'speaking'])
    ->middleware(['auth', 'subscription:partial_speaking']);

// Practice module routes
Route::get('/practice/reading', [PracticeController::class, 'reading'])
    ->middleware(['auth', 'subscription:practice_reading']);

Route::get('/practice/writing', [PracticeController::class, 'writing'])
    ->middleware(['auth', 'subscription:practice_writing']);

Route::get('/practice/listening', [PracticeController::class, 'listening'])
    ->middleware(['auth', 'subscription:practice_listening']);

Route::get('/practice/speaking', [PracticeController::class, 'speaking'])
    ->middleware(['auth', 'subscription:practice_speaking']);
```

**Available Resource Types:**
- `full_mock_test` - Full mock test access
- `partial_reading` - Reading partial test access
- `partial_writing` - Writing partial test access
- `partial_listening` - Listening partial test access
- `partial_speaking` - Speaking partial test access
- `practice_reading` - Reading practice module
- `practice_listening` - Listening practice module
- `practice_writing` - Writing practice module
- `practice_speaking` - Speaking practice module

### Using the HasSubscription Trait Methods

The `HasSubscription` trait is already applied to the `User` model and provides helper methods for checking subscription status and limits.

**Check Active Subscription:**

```php
// Check if user has any active subscription
if ($user->hasActiveSubscription()) {
    // User has active subscription
}

// Check if subscription is expired
if ($user->isSubscriptionExpired()) {
    // Subscription has expired
}
```

**Check Resource Access:**

```php
// Check if user can access a specific resource
if ($user->canAccessResource('full_mock_test')) {
    // User can take full mock test
}

if ($user->canAccessResource('partial_reading')) {
    // User can take reading partial test
}

if ($user->canAccessResource('practice_writing')) {
    // User can use writing practice
}
```

**Get Remaining Limits:**

```php
// Full mock tests
$remaining = $user->getRemainingFullMockTests();
// Returns: integer (0+) or null (unlimited)

// Partial tests - separate for each part
$readingRemaining = $user->getRemainingPartialReading();
$writingRemaining = $user->getRemainingPartialWriting();
$listeningRemaining = $user->getRemainingPartialListening();
$speakingRemaining = $user->getRemainingPartialSpeaking();

// Practice modules
$writingPracticeRemaining = $user->getRemainingWritingPractice();
$speakingPracticeRemaining = $user->getRemainingSpeakingPractice();
// Returns: integer (0+) or null (unlimited or not enabled)
```

**Check Practice Module Enabled:**

```php
// Check if a practice module is enabled
if ($user->isPracticeModuleEnabled('reading')) {
    // Reading practice is enabled (unlimited)
}

if ($user->isPracticeModuleEnabled('writing')) {
    // Writing practice is enabled (check limit separately)
}
```

**Increment Usage Counters:**

```php
// After user completes a resource, increment the counter
$user->incrementUsage('full_mock_test');
$user->incrementUsage('partial_reading');
$user->incrementUsage('partial_writing');
$user->incrementUsage('practice_writing');

// This should be called AFTER successful completion of the resource
// Failed attempts or incomplete sessions should not increment
```

### Controller Implementation Example

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function startFullMockTest(Request $request)
    {
        $user = $request->user();

        // Middleware already checked access, but we can double-check
        if (!$user->canAccessResource('full_mock_test')) {
            return back()->with('error', 'You have reached your limit for full mock tests.');
        }

        // Start the exam...
        $exam = $this->createExam($user);

        // After successful start, increment usage
        $user->incrementUsage('full_mock_test');

        return view('exam.start', compact('exam'));
    }

    public function startPartialReading(Request $request)
    {
        $user = $request->user();

        // Check access for reading specifically
        if (!$user->canAccessResource('partial_reading')) {
            return back()->with('error', 'You have reached your limit for reading partial tests.');
        }

        // Start the reading part...
        $exam = $this->createReadingPartExam($user);

        // Increment ONLY the reading counter
        $user->incrementUsage('partial_reading');

        return view('exam.reading', compact('exam'));
    }
}
```

### Frontend Usage (Vue/Inertia)

**Displaying Subscription Info:**

```vue
<script setup lang="ts">
interface Subscription {
    package_name: string;
    expires_at: string;
    days_remaining: number;
    is_active: boolean;
    full_mock_test: {
        enabled: boolean;
        limit: number | null;
        used: number;
        remaining: number | null;
    };
    partial_tests: {
        reading: { enabled: boolean; limit: number | null; used: number; remaining: number | null; };
        writing: { enabled: boolean; limit: number | null; used: number; remaining: number | null; };
        listening: { enabled: boolean; limit: number | null; used: number; remaining: number | null; };
        speaking: { enabled: boolean; limit: number | null; used: number; remaining: number | null; };
    };
}

const props = defineProps<{
    subscription: Subscription | null;
}>();

// Helper to show remaining text
const getRemainingText = (remaining: number | null): string => {
    if (remaining === null) return 'Unlimited';
    return `${remaining} remaining`;
};
</script>

<template>
    <div v-if="subscription && subscription.is_active">
        <h3>{{ subscription.package_name }}</h3>
        <p>Expires in {{ subscription.days_remaining }} days</p>

        <!-- Full mock tests -->
        <div v-if="subscription.full_mock_test.enabled">
            <p>Full Mock Tests: {{ getRemainingText(subscription.full_mock_test.remaining) }}</p>
            <progress
                :value="subscription.full_mock_test.used"
                :max="subscription.full_mock_test.limit"
            ></progress>
        </div>

        <!-- Partial tests - show each part separately -->
        <div class="partial-tests">
            <h4>Partial Mock Tests</h4>
            <div v-if="subscription.partial_tests.reading.enabled">
                <p>Reading: {{ getRemainingText(subscription.partial_tests.reading.remaining) }}</p>
            </div>
            <div v-if="subscription.partial_tests.writing.enabled">
                <p>Writing: {{ getRemainingText(subscription.partial_tests.writing.remaining) }}</p>
            </div>
            <div v-if="subscription.partial_tests.listening.enabled">
                <p>Listening: {{ getRemainingText(subscription.partial_tests.listening.remaining) }}</p>
            </div>
            <div v-if="subscription.partial_tests.speaking.enabled">
                <p>Speaking: {{ getRemainingText(subscription.partial_tests.speaking.remaining) }}</p>
            </div>
        </div>
    </div>

    <div v-else>
        <p>No active subscription. Please contact your administrator.</p>
    </div>
</template>
```

### Access Control Flow

**Complete Flow for Taking a Full Mock Test:**

1. **User clicks "Start Full Mock Test"**
2. **Route middleware checks access:**
   ```php
   Route::post('/exam/start', ...)
       ->middleware(['auth', 'subscription:full_mock_test']);
   ```
3. **Middleware verifies:**
   - User has active subscription
   - Subscription not expired
   - User has remaining full mock test limit (or unlimited)
4. **If access granted:**
   - Controller creates exam
   - User takes exam
   - On completion, `incrementUsage('full_mock_test')` is called
   - Counter increments: `full_mock_tests_used++`
5. **If access denied:**
   - User redirected with error message
   - Message varies based on reason (no subscription, expired, limit reached)

**Complete Flow for Taking a Partial Reading Test:**

1. **User clicks "Start Reading Part"**
2. **Route middleware checks access:**
   ```php
   Route::post('/exam/reading', ...)
       ->middleware(['auth', 'subscription:partial_reading']);
   ```
3. **Middleware verifies:**
   - User has active subscription
   - Subscription not expired
   - User has remaining reading partial limit (or unlimited)
   - **Note: Other partial test limits (writing, listening, speaking) are NOT checked**
4. **If access granted:**
   - Controller creates reading exam
   - User takes reading test
   - On completion, `incrementUsage('partial_reading')` is called
   - **Only** `partial_reading_used` increments (writing, listening, speaking counters unchanged)
5. **User can still access:**
   - Writing parts (separate limit)
   - Listening parts (separate limit)
   - Speaking parts (separate limit)

### Important Notes

1. **Admin users bypass all subscription checks** - The middleware automatically allows admins through
2. **Increment usage AFTER successful completion** - Don't increment on start, only on completion
3. **Null means unlimited** - A null limit value means the resource is unlimited
4. **Practice modules require enable check** - Always check `isPracticeModuleEnabled()` before checking limits
5. **Separate counters for each partial test part** - Reading, writing, listening, and speaking are tracked independently
6. **Middleware handles redirects** - If access is denied, middleware handles the redirect/error message
7. **Session completion** - If a user starts a session before expiry/limit, allow them to complete it
