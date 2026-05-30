# Practice Limit Testing Guide

This guide helps you manually test the Writing and Speaking practice limit tracking functionality.

## Prerequisites

1. Laravel application is running (`composer run dev` or `npm run dev`)
2. Database is migrated
3. You have admin and student accounts

## Test Scenarios

### Scenario 1: Writing Practice with Limited Attempts

**Setup:**
1. Login as admin
2. Create a package:
   - Name: "Writing Practice Test"
   - Duration: 30 days
   - Price: 1000
   - Practice Writing: ✓ Enabled, Limit: 3
   - Practice Speaking: ✓ Enabled, Limit: 5
   - All other modules: Disabled or unlimited

3. Assign this package to a test student

**Test Steps:**
```bash
# 1. Login as the test student
# 2. Visit: https://englishtherapy-ielts.test/student/dashboard
# Expected: Should see "Writing Practice: 0 / 3 used"

# 3. Visit: https://englishtherapy-ielts.test/writing
# Expected: Page loads successfully

# 4. Complete a writing practice (any Task 1 or Task 2)
# - Start the exam
# - Write at least 50 words
# - Submit for AI evaluation
# - Wait for results
# Expected: Evaluation completes successfully

# 5. Go back to dashboard: /student/dashboard
# Expected: Should now show "Writing Practice: 1 / 3 used"

# 6. Repeat steps 3-5 two more times
# Expected: After 2nd attempt: "2 / 3 used"
#           After 3rd attempt: "3 / 3 used"

# 7. Try to visit /writing again
# Expected: Redirected to dashboard with error message:
#          "You have reached your limit for Writing practice"

# 8. Try to submit via API (optional - requires API testing tool)
POST /api/writing/practice-evaluate
{
  "writing": [{
    "q": "Test question",
    "ans": "Test answer with at least 50 characters in the response"
  }]
}
# Expected: 403 Forbidden response
```

**Verification:**
- Check `user_subscriptions` table:
  ```sql
  SELECT practice_writing_used, practice_writing_limit
  FROM user_subscriptions
  WHERE user_id = [student_id];
  ```
  Expected: `practice_writing_used = 3`

- Check `subscription_history` table:
  ```sql
  SELECT * FROM subscription_history
  WHERE user_id = [student_id]
  ORDER BY created_at DESC;
  ```
  Expected: See "assigned" action with admin info

### Scenario 2: Speaking Practice with Limited Attempts

**Setup:**
Same package from Scenario 1 (Writing: 3, Speaking: 5)

**Test Steps:**
```bash
# 1. Login as the test student
# 2. Visit: https://englishtherapy-ielts.test/student/dashboard
# Expected: Should see "Speaking Practice: 0 / 5 used"

# 3. Visit: https://englishtherapy-ielts.test/speaking
# Expected: Page loads successfully

# 4. Complete a speaking practice session
# - Click "Start Part 1" (or any part)
# - Allow microphone access
# - Wait for examiner question
# - Speak your answer (minimum 5-30 seconds depending on part)
# - Submit response
# - Continue until session ends
# - View evaluation results
# Expected: Session completes and shows band scores

# 5. Go back to dashboard
# Expected: Should now show "Speaking Practice: 1 / 5 used"

# 6. Repeat steps 3-5 four more times
# Expected: Counter increments each time: 2/5, 3/5, 4/5, 5/5

# 7. Try to visit /speaking again
# Expected: Redirected to dashboard with error:
#          "You have reached your limit for Speaking practice"

# 8. Try to start session via API (optional)
POST /api/speaking/start-session
{ "part": "1" }
# Expected: 403 Forbidden response
```

**Verification:**
```sql
SELECT practice_speaking_used, practice_speaking_limit
FROM user_subscriptions
WHERE user_id = [student_id];
```
Expected: `practice_speaking_used = 5`

### Scenario 3: Unlimited Practice

**Setup:**
1. Create a new package:
   - Name: "Unlimited Practice"
   - Practice Writing: ✓ Enabled, Limit: **Leave empty** (null)
   - Practice Speaking: ✓ Enabled, Limit: **Leave empty** (null)

2. Assign to a different test student

**Test Steps:**
```bash
# 1. Login as student
# 2. Visit dashboard
# Expected: "Writing Practice: Unlimited"
#          "Speaking Practice: Unlimited"

# 3. Complete 10+ writing practices
# Expected: All succeed, no blocking

# 4. Complete 10+ speaking practices
# Expected: All succeed, no blocking

# 5. Check dashboard again
# Expected: Still shows "Unlimited" (not showing used count)
```

### Scenario 4: Disabled Practice Modules

**Setup:**
1. Create a package:
   - Name: "No Practice Access"
   - Practice Writing: ❌ **Disabled**
   - Practice Speaking: ❌ **Disabled**

2. Assign to a test student

**Test Steps:**
```bash
# 1. Login as student
# 2. Visit dashboard
# Expected: Writing and Speaking practice modules not shown or marked as "Not Available"

# 3. Try to visit /writing
# Expected: Redirected with error:
#          "Writing practice is not available in your subscription plan"

# 4. Try to visit /speaking
# Expected: Redirected with error:
#          "Speaking practice is not available in your subscription plan"
```

### Scenario 5: Expired Subscription

**Setup:**
1. Use existing package with practice enabled
2. Manually set subscription expiry date to past:
   ```sql
   UPDATE user_subscriptions
   SET expires_at = '2026-01-01 00:00:00'
   WHERE user_id = [student_id];
   ```

**Test Steps:**
```bash
# 1. Login as student
# 2. Visit dashboard
# Expected: Shows "Expired" status with red indicator

# 3. Try to visit /writing
# Expected: Redirected with error:
#          "Your subscription has expired"

# 4. Try to visit /speaking
# Expected: Same error as above
```

### Scenario 6: Admin Bypass

**Test Steps:**
```bash
# 1. Login as admin user
# 2. Admin should NOT have any subscription assigned

# 3. Visit /writing
# Expected: Page loads successfully (bypass subscription check)

# 4. Visit /speaking
# Expected: Page loads successfully (bypass subscription check)

# 5. Complete practices
# Expected: Works, but usage NOT tracked (admin bypass)
```

### Scenario 7: Package Change Mid-Usage

**Setup:**
1. Student has "Package A" with Writing: 5 attempts
2. Student uses 3 attempts (2 remaining)

**Test Steps:**
```bash
# 1. As admin, go to /subscriptions/users?package=[PackageA_ID]
# 2. Click "Change" on the student
# 3. Select "Package B" with Writing: 10 attempts
# 4. Confirm change

# 5. Login as student and visit dashboard
# Expected: Counter resets to 0 / 10 used (fresh start with new package)

# 6. Check subscription_history table
# Expected: Shows action='upgraded' with old and new package IDs
```

## SQL Queries for Verification

### Check User's Current Subscription
```sql
SELECT
    u.name,
    u.email,
    sp.name as package_name,
    us.subscribed_at,
    us.expires_at,
    us.is_active,
    us.practice_writing_used,
    sp.practice_writing_limit,
    us.practice_speaking_used,
    sp.practice_speaking_limit
FROM user_subscriptions us
JOIN users u ON us.user_id = u.id
JOIN subscription_packages sp ON us.package_id = sp.id
WHERE u.email = 'student@example.com'
AND us.is_active = 1;
```

### Check All Practice Usage History
```sql
SELECT
    u.name,
    us.practice_writing_used,
    us.practice_speaking_used,
    us.created_at,
    us.updated_at
FROM user_subscriptions us
JOIN users u ON us.user_id = u.id
WHERE u.email = 'student@example.com'
ORDER BY us.created_at DESC;
```

### Check Subscription Change History
```sql
SELECT
    u.name as user_name,
    old_pkg.name as old_package,
    new_pkg.name as new_package,
    sh.action,
    admin.name as changed_by,
    sh.notes,
    sh.created_at
FROM subscription_history sh
JOIN users u ON sh.user_id = u.id
LEFT JOIN subscription_packages old_pkg ON sh.old_package_id = old_pkg.id
LEFT JOIN subscription_packages new_pkg ON sh.new_package_id = new_pkg.id
LEFT JOIN users admin ON sh.performed_by = admin.id
WHERE u.email = 'student@example.com'
ORDER BY sh.created_at DESC;
```

## Expected Database State After Complete Test

After running all scenarios, you should see:

**user_subscriptions table:**
- Multiple records showing different packages and usage counts
- `is_active = 1` for currently active subscription only
- Usage counters accurately reflect completed practices

**subscription_history table:**
- "assigned" action when first assigned
- "upgraded" action when package changed
- "cancelled" action when subscription removed
- Each record has admin's user_id in `performed_by`
- Descriptive notes for each action

## Troubleshooting

**Issue: Tests complete but counter doesn't increment**
- Check: Is `incrementUsage('practice_writing')` being called in controller?
- Check: Does user have active subscription?
- Check: Look at Laravel logs for errors

**Issue: Can access practice even when limit reached**
- Check: Is middleware applied to route? (`->middleware('subscription:practice_writing')`)
- Check: Is `CheckSubscription` middleware registered in `bootstrap/app.php`?

**Issue: Dashboard shows wrong remaining count**
- Check: `getRemainingWritingPractice()` method in `HasSubscription` trait
- Verify: Package limits in database match what you set

**Issue: Admin can't access (unexpected)**
- Check: `isAdmin()` method returns true for admin users
- Check: Middleware allows admin bypass

## Success Criteria

✓ Writing practice blocks after reaching limit
✓ Speaking practice blocks after reaching limit
✓ Dashboard shows accurate usage counts
✓ Unlimited packages allow infinite attempts
✓ Disabled modules block access
✓ Expired subscriptions block access
✓ Admin can bypass all restrictions
✓ Package changes reset counters
✓ Subscription history logs all actions
✓ Proper error messages shown to users
