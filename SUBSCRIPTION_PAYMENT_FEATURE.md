# Subscription Payment Feature

## Overview

Enhanced subscription assignment system that allows admins to assign subscriptions with full or partial payment options, track payment history, and manage payment status.

---

## Feature Requirements

### 1. Subscription Assignment with Payment Options

When an admin assigns a subscription to a user, they can choose between:

#### **Option A: Full Payment**
- User pays the complete package price
- User gets access until the package's `duration` date
- Expiry date is set to: `package.duration`
- Payment status: **"Paid"**
- No due amount

#### **Option B: Partial Payment**
- Admin enters a partial amount (less than package price)
- Admin manually selects:
  - **Custom Expiry Date** (when subscription access ends)
  - **Due Date** (when remaining payment is expected)
- Payment status: **"Partial"**
- Due amount: `package.price - paid_amount`

---

## Database Schema

### New Table: `payment_history`

```sql
CREATE TABLE payment_history (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,

    -- Payment Details
    package_price DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) NOT NULL,
    due_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    payment_status ENUM('paid', 'partial', 'due') NOT NULL,

    -- Dates
    payment_date DATE NOT NULL,
    due_date DATE NULL,
    expiry_date DATE NOT NULL,

    -- Meta
    payment_method VARCHAR(50) NULL,  -- Optional: cash, bkash, card, etc.
    transaction_note TEXT NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,  -- Admin who assigned

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES subscription_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_due_date (due_date),
    INDEX idx_payment_date (payment_date)
);
```

### Update Table: `user_subscriptions`

Add a reference to payment:

```sql
ALTER TABLE user_subscriptions
ADD COLUMN payment_id BIGINT UNSIGNED NULL AFTER id,
ADD FOREIGN KEY (payment_id) REFERENCES payment_history(id) ON DELETE SET NULL;
```

### Update Table: `subscription_packages`

**Change `duration` from INTEGER to DATE:**

```sql
-- Migration: Change duration from integer to date

-- Step 1: Add temporary column
ALTER TABLE subscription_packages
ADD COLUMN duration_date DATE NULL;

-- Step 2: Convert existing integer duration to dates
-- (Assuming duration was in days, convert to date from now)
UPDATE subscription_packages
SET duration_date = DATE_ADD(CURDATE(), INTERVAL duration DAY)
WHERE duration IS NOT NULL;

-- Step 3: Drop old duration column
ALTER TABLE subscription_packages
DROP COLUMN duration;

-- Step 4: Rename new column to duration
ALTER TABLE subscription_packages
CHANGE COLUMN duration_date duration DATE NOT NULL;
```

**How it works:**
- **Before:** `duration = 30` (30 days as integer)
- **After:** `duration = '2026-12-31'` (actual date)

**Admin Experience:**
- When creating/editing a package, admin selects a date using date picker
- Label: "Duration (Valid Until)"
- Example: "December 31, 2026"
- Package expires on that specific date

**Expiry Calculation:**
```php
// Old way (integer duration):
$expiryDate = now()->addDays($package->duration);

// New way (duration as date):
$expiryDate = $package->duration;
```

**Benefits:**
- ✅ More flexible - set any specific expiry date
- ✅ No calculation needed - expiry date is directly in duration field
- ✅ Column name stays the same (`duration`)
- ✅ Easier for admins to understand

---

## Payment Status Logic

### Status: **"Paid"** (Green Badge)
- `paid_amount >= package_price`
- No due amount
- No due date required

### Status: **"Partial"** (Yellow/Orange Badge)
- `0 < paid_amount < package_price`
- `due_amount > 0`
- `due_date` is in the future (not yet overdue)
- User has active subscription access

### Status: **"Due"** (Red Badge)
- `due_amount > 0`
- `due_date` has passed (overdue)
- User subscription might be deactivated or restricted

### Calculated Field: **"Earned"** (Filter Only)
- Total `paid_amount` across all transactions
- Used for revenue reporting

---

## UI/UX Flow

### 1. Subscription Assignment Modal (Enhanced)

**Location:** `/subscriptions/users?package=1`

**Current Modal:**
```
[Assign Package to User]
User: John Doe
Package: Premium Package (BDT 5000 / Duration: Dec 31, 2026)
[Assign Button]
```

**New Modal:**
```
┌─────────────────────────────────────────┐
│ Assign Package to User                  │
├─────────────────────────────────────────┤
│ User: John Doe                          │
│ Package: Premium Package                │
│ Package Price: BDT 5,000                │
│ Package Duration: Dec 31, 2026          │
│                                         │
│ Payment Option: ⚪ Full  ⚪ Partial     │
│                                         │
│ ─── If Full Selected ───                │
│ Amount: BDT 5,000 (readonly)           │
│ Expiry Date: Dec 31, 2026 (from pkg)  │
│                                         │
│ ─── If Partial Selected ───             │
│ Paid Amount: [____] BDT                │
│ Due Amount: 2,000 BDT (calculated)     │
│ Expiry Date: [Calendar Picker]         │
│   Max: Dec 31, 2026 (package limit)    │
│ Due Date: [Calendar Picker]            │
│                                         │
│ Payment Method: [Dropdown] (Optional)   │
│   - Cash                                │
│   - bKash                               │
│   - Bank Transfer                       │
│   - Card                                │
│   - Other                               │
│                                         │
│ Note: [Optional text field]            │
│                                         │
│ [Cancel]  [Assign & Record Payment]    │
└─────────────────────────────────────────┘
```

**Validation Rules:**
- Partial payment: `0 < paid_amount < package_price`
- Partial payment: `due_date` must be after today
- Partial payment: `expiry_date` must be after today AND cannot exceed package's `duration` date
- Full payment: user expiry set to package's `duration` date

---

### 2. Payment History Page (NEW)

**Route:** `/payment-history`

**Controller:** `PaymentHistoryController`

**Page:** `resources/js/pages/PaymentHistory/Index.vue`

#### Page Header
```
┌────────────────────────────────────────────┐
│ 💰 Payment History                         │
│ Track subscription payments and dues       │
│                                            │
│ [Search: by name/roll/phone]   [Export]   │
└────────────────────────────────────────────┘
```

#### Filter Tabs
```
┌────────────────────────────────────────────┐
│ [ All (120) ] [ Paid (80) ] [ Partial (25) ] [ Due (15) ]  │
└────────────────────────────────────────────┘
```

#### Summary Cards (Top Row)
```
┌────────────┬────────────┬────────────┬────────────┐
│ Total Earned                                      │
│ 💵 BDT 450,000        📊 +12% this month          │
├────────────┬────────────┬────────────┬────────────┤
│ Paid       │ Partial    │ Due        │ Total Due  │
│ 80         │ 25         │ 15         │ BDT 75,000 │
│ BDT 400K   │ BDT 50K    │ BDT 75K    │            │
└────────────┴────────────┴────────────┴────────────┘
```

#### Table Structure
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ Date       │ User           │ Package      │ Price  │ Paid   │ Due    │ Status │ Due Date   │ Expiry     │ Actions  │
├──────────────────────────────────────────────────────────────────────────────┤
│ 2026-03-10 │ John Doe       │ Premium      │ 5,000  │ 5,000  │ 0      │ ✅ Paid │ -          │ 2026-04-09 │ [👁️ View] │
│            │ Roll: 101      │              │        │        │        │        │            │            │          │
├──────────────────────────────────────────────────────────────────────────────┤
│ 2026-03-09 │ Jane Smith     │ Standard     │ 3,000  │ 2,000  │ 1,000  │ ⚠️ Partial │ 2026-03-20 │ 2026-04-08 │ [👁️ View] │
│            │ Roll: 102      │              │        │        │        │        │ (10 days)  │            │ [💰 Pay]  │
├──────────────────────────────────────────────────────────────────────────────┤
│ 2026-03-05 │ Mike Johnson   │ Basic        │ 2,000  │ 1,000  │ 1,000  │ 🔴 Due  │ 2026-03-08 │ 2026-04-04 │ [👁️ View] │
│            │ Roll: 103      │              │        │        │        │        │ (Overdue!) │            │ [💰 Pay]  │
└──────────────────────────────────────────────────────────────────────────────┘
```

#### Payment Detail Modal (View Action)
```
┌─────────────────────────────────────────┐
│ 📄 Payment Details                      │
├─────────────────────────────────────────┤
│ Payment ID: #1234                       │
│ Date: March 10, 2026                    │
│                                         │
│ User Information:                       │
│ • Name: John Doe                        │
│ • Roll: 101                             │
│ • Phone: 01712345678                    │
│                                         │
│ Package Information:                    │
│ • Package: Premium Package              │
│ • Duration: Dec 31, 2026               │
│ • Price: BDT 5,000                      │
│                                         │
│ Payment Summary:                        │
│ • Paid Amount: BDT 5,000               │
│ • Due Amount: BDT 0                    │
│ • Status: ✅ Paid                       │
│ • Payment Method: bKash                 │
│                                         │
│ Dates:                                  │
│ • Payment Date: 2026-03-10             │
│ • Expiry Date: 2026-04-09              │
│ • Due Date: -                          │
│                                         │
│ Note: Full payment received via bKash   │
│                                         │
│ Assigned By: Admin User                 │
│                                         │
│ [Close]  [Print Receipt]               │
└─────────────────────────────────────────┘
```

#### Record Additional Payment Modal (Pay Action)
```
┌─────────────────────────────────────────┐
│ 💰 Record Additional Payment            │
├─────────────────────────────────────────┤
│ User: Jane Smith (Roll: 102)           │
│ Package: Standard Package               │
│                                         │
│ Previous Payment: BDT 2,000             │
│ Remaining Due: BDT 1,000                │
│                                         │
│ New Payment Amount: [____] BDT          │
│ Payment Method: [Dropdown]              │
│ Payment Date: [Date Picker]             │
│                                         │
│ Extend Expiry Date? ☑️                  │
│ New Expiry Date: [Date Picker]         │
│                                         │
│ Note: [Optional]                        │
│                                         │
│ [Cancel]  [Record Payment]             │
└─────────────────────────────────────────┘
```

---

### 3. App Sidebar Navigation (Update)

**Location:** `resources/js/components/AppSidebar.vue`

**Add New Menu Item:**

```javascript
{
    title: 'Payment History',
    href: '/payment-history',
    icon: Banknote,  // or CreditCard / DollarSign from lucide-vue-next
    badge: duePaymentsCount > 0 ? duePaymentsCount : null,
    badgeColor: 'red'
}
```

**Position:** After "Subscriptions" section, before "Settings"

**Sidebar Structure:**
```
Dashboard
Students
Subscriptions
  └─ Packages
  └─ Users
💰 Payment History  [🔴 15]  ← NEW
Coaching IPs
Settings
```

---

## Backend Implementation Guide

### 1. Migration

**File:** `database/migrations/xxxx_create_payment_history_table.php`

```php
Schema::create('payment_history', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('subscription_id')->constrained('user_subscriptions')->onDelete('cascade');
    $table->foreignId('package_id')->constrained('subscription_packages')->onDelete('cascade');

    $table->decimal('package_price', 10, 2);
    $table->decimal('paid_amount', 10, 2);
    $table->decimal('due_amount', 10, 2)->default(0);
    $table->enum('payment_status', ['paid', 'partial', 'due']);

    $table->date('payment_date');
    $table->date('due_date')->nullable();
    $table->date('expiry_date');

    $table->string('payment_method', 50)->nullable();
    $table->text('transaction_note')->nullable();
    $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');

    $table->timestamps();

    $table->index(['user_id', 'payment_status']);
    $table->index('due_date');
});

// Update user_subscriptions table
Schema::table('user_subscriptions', function (Blueprint $table) {
    $table->foreignId('payment_id')->nullable()->after('id')
        ->constrained('payment_history')->onDelete('set null');
});
```

---

### 2. Model

**File:** `app/Models/PaymentHistory.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $table = 'payment_history';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'package_id',
        'package_price',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_date',
        'due_date',
        'expiry_date',
        'payment_method',
        'transaction_note',
        'assigned_by',
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Status Methods
    public function isDue()
    {
        return $this->payment_status === 'due' ||
            ($this->due_amount > 0 && $this->due_date && $this->due_date->isPast());
    }

    public function isPartial()
    {
        return $this->payment_status === 'partial' &&
            $this->due_amount > 0 &&
            (!$this->due_date || $this->due_date->isFuture());
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid' && $this->due_amount == 0;
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopeDue($query)
    {
        return $query->where('payment_status', 'due')
            ->orWhere(function ($q) {
                $q->where('due_amount', '>', 0)
                  ->where('due_date', '<', now());
            });
    }
}
```

**Update UserSubscription Model:**

```php
// Add to UserSubscription model
public function payment()
{
    return $this->belongsTo(PaymentHistory::class, 'payment_id');
}
```

**Update SubscriptionPackage Model:**

**File:** `app/Models/SubscriptionPackage.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'name',
        'price',
        'duration',  // Now a DATE field instead of integer
        'description',
        // ... other fields
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'date',  // Cast to date (was integer before)
        'is_active' => 'boolean',
    ];

    // Check if package is still valid
    public function isValid(): bool
    {
        return $this->duration >= now()->toDateString();
    }

    // Get days remaining until expiry
    public function daysRemaining(): int
    {
        return max(0, now()->diffInDays($this->duration, false));
    }

    // Format duration date for display
    public function getFormattedDurationAttribute(): string
    {
        return $this->duration->format('M d, Y');
    }
}
```

---

### 3. Controller

**File:** `app/Http/Controllers/PaymentHistoryController.php`

```php
namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $search = $request->get('search');

        $query = PaymentHistory::query()
            ->with(['user', 'package', 'assignedBy']);

        // Apply filters
        if ($filter === 'paid') {
            $query->paid();
        } elseif ($filter === 'partial') {
            $query->partial();
        } elseif ($filter === 'due') {
            $query->due();
        }

        // Search
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest('payment_date')->paginate(20);

        // Summary statistics
        $stats = [
            'total_earned' => PaymentHistory::sum('paid_amount'),
            'paid_count' => PaymentHistory::paid()->count(),
            'partial_count' => PaymentHistory::partial()->count(),
            'due_count' => PaymentHistory::due()->count(),
            'total_due' => PaymentHistory::where('due_amount', '>', 0)->sum('due_amount'),
        ];

        return Inertia::render('PaymentHistory/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'filter' => $filter,
            'search' => $search,
        ]);
    }

    public function show(PaymentHistory $payment)
    {
        $payment->load(['user', 'package', 'subscription', 'assignedBy']);

        return Inertia::render('PaymentHistory/Show', [
            'payment' => $payment,
        ]);
    }

    public function recordAdditionalPayment(Request $request, PaymentHistory $payment)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:' . $payment->due_amount],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_date' => ['required', 'date'],
            'new_expiry_date' => ['nullable', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Update payment record
        $newPaidAmount = $payment->paid_amount + $validated['amount'];
        $newDueAmount = $payment->package_price - $newPaidAmount;

        $payment->update([
            'paid_amount' => $newPaidAmount,
            'due_amount' => $newDueAmount,
            'payment_status' => $newDueAmount <= 0 ? 'paid' : 'partial',
            'expiry_date' => $validated['new_expiry_date'] ?? $payment->expiry_date,
        ]);

        // Create payment transaction log (optional separate table)
        // PaymentTransaction::create([...]);

        // Update subscription expiry if provided
        if (isset($validated['new_expiry_date']) && $payment->subscription) {
            $payment->subscription->update([
                'expires_at' => $validated['new_expiry_date'],
            ]);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}
```

---

### 4. Update SubscriptionUsersController

**File:** `app/Http/Controllers/SubscriptionUsersController.php`

**Modify the `store()` method:**

```php
public function store(Request $request)
{
    // First, get the package to use in validation
    $package = SubscriptionPackage::findOrFail($request->package_id);

    $validated = $request->validate([
        'user_id' => ['required', 'exists:users,id'],
        'package_id' => ['required', 'exists:subscription_packages,id'],
        'payment_type' => ['required', 'in:full,partial'],
        'paid_amount' => ['required_if:payment_type,partial', 'numeric', 'min:0'],
        'expiry_date' => [
            'required_if:payment_type,partial',
            'date',
            'after:today',
            'before_or_equal:' . $package->duration,  // Cannot exceed package expiry
        ],
        'due_date' => ['required_if:payment_type,partial', 'date', 'after:today'],
        'payment_method' => ['nullable', 'string', 'max:50'],
        'note' => ['nullable', 'string', 'max:500'],
    ]);

    $user = User::findOrFail($validated['user_id']);

    // Calculate payment details
    $isFullPayment = $validated['payment_type'] === 'full';
    $paidAmount = $isFullPayment ? $package->price : $validated['paid_amount'];
    $dueAmount = $package->price - $paidAmount;
    $expiryDate = $isFullPayment
        ? $package->duration  // Use package's duration date directly
        : $validated['expiry_date'];

    // Delete existing subscriptions
    UserSubscription::where('user_id', $user->id)->delete();

    // Create new subscription
    $subscription = UserSubscription::create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'subscribed_at' => now(),
        'expires_at' => $expiryDate,
        'is_active' => true,
        'full_mock_tests_used' => 0,
        'partial_reading_used' => 0,
        'partial_writing_used' => 0,
        'partial_listening_used' => 0,
        'partial_speaking_used' => 0,
        'practice_writing_used' => 0,
        'practice_speaking_used' => 0,
    ]);

    // Create payment record
    $payment = PaymentHistory::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'paid_amount' => $paidAmount,
        'due_amount' => $dueAmount,
        'payment_status' => $dueAmount > 0 ? 'partial' : 'paid',
        'payment_date' => now(),
        'due_date' => $isFullPayment ? null : $validated['due_date'],
        'expiry_date' => $expiryDate,
        'payment_method' => $validated['payment_method'] ?? null,
        'transaction_note' => $validated['note'] ?? null,
        'assigned_by' => Auth::id(),
    ]);

    // Link payment to subscription
    $subscription->update(['payment_id' => $payment->id]);

    // Log subscription history
    SubscriptionHistory::create([
        'user_id' => $user->id,
        'old_package_id' => null,
        'new_package_id' => $package->id,
        'action' => 'assigned',
        'performed_by' => Auth::id(),
        'notes' => "Assigned with {$validated['payment_type']} payment (BDT {$paidAmount})",
    ]);

    return redirect()->back()->with('success', 'Subscription assigned and payment recorded.');
}
```

---

### 5. Routes

**File:** `routes/web.php`

```php
// Payment History Routes (Admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('payment-history', [PaymentHistoryController::class, 'index'])
        ->name('payment-history.index');
    Route::get('payment-history/{payment}', [PaymentHistoryController::class, 'show'])
        ->name('payment-history.show');
    Route::post('payment-history/{payment}/add-payment', [PaymentHistoryController::class, 'recordAdditionalPayment'])
        ->name('payment-history.add-payment');
    Route::get('payment-history/export', [PaymentHistoryController::class, 'export'])
        ->name('payment-history.export');
});
```

---

## Frontend Implementation Guide

### 1. Enhanced Assignment Modal Component

**File:** `resources/js/components/Subscription/AssignmentModal.vue`

**Key Features:**
- Radio button toggle: Full / Partial
- Conditional form fields based on selection
- Real-time due amount calculation
- Date pickers for expiry and due dates
- Payment method dropdown
- Validation messages

---

### 2. Payment History Page

**File:** `resources/js/pages/PaymentHistory/Index.vue`

**Components:**
- Summary statistics cards
- Filter tabs (All, Paid, Partial, Due)
- Search bar
- Data table with pagination
- View details modal
- Record payment modal
- Export functionality

---

### 3. Update Package Management Forms

**Files to Update:**
- `resources/js/pages/Subscriptions/Packages/Create.vue`
- `resources/js/pages/Subscriptions/Packages/Edit.vue`

**Changes:**
- Change duration input from `number` to `date` picker
- Field name: `duration` (stays the same)
- Input type: `number` → `date`
- Label: "Duration"
- Help text: "Select the date when this package will expire"

**Example (Create/Edit Package Form):**
```vue
<div>
    <label for="duration" class="block text-sm font-medium text-gray-700">
        Duration *
    </label>
    <input
        v-model="form.duration"
        type="date"
        id="duration"
        :min="minDate"
        required
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
    />
    <p class="mt-1 text-sm text-gray-500">
        Select the date when this package will expire
    </p>
</div>

<script setup>
import { computed } from 'vue';

// Minimum date is tomorrow
const minDate = computed(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
});
</script>
```

**Display Format (Package List/Details):**
```vue
<!-- Before -->
<span>{{ package.duration }} days</span>

<!-- After -->
<span>Duration: {{ formatDate(package.duration) }}</span>
<!-- Example output: "Duration: Dec 31, 2026" -->
```

**Format Helper:**
```javascript
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
```

---

### 4. Update AppSidebar

**File:** `resources/js/components/AppSidebar.vue`

**Add:**
```vue
import { Banknote } from 'lucide-vue-next';

// In navigation array
{
    title: 'Payment History',
    href: '/payment-history',
    icon: Banknote,
    // Optional: Show due count badge
}
```

---

## Additional Features (Optional Enhancements)

### 1. Auto Due Status Update (Scheduled Job)

**File:** `app/Console/Commands/UpdateDuePayments.php`

```php
// Run daily to update payment status from 'partial' to 'due' if due_date passed
PaymentHistory::where('payment_status', 'partial')
    ->where('due_date', '<', now())
    ->where('due_amount', '>', 0)
    ->update(['payment_status' => 'due']);
```

**Register in:** `app/Console/Kernel.php` or `routes/console.php`

---

### 2. Payment Reminders

- Send SMS/Email reminders X days before due_date
- Notify admins of overdue payments
- Show due payments dashboard widget

---

### 3. Payment Receipt Generation

- Generate PDF receipts
- Include QR code for verification
- Email receipt to user

---

### 4. Advanced Reporting

- Monthly revenue reports
- Payment collection trends
- Due payment aging report
- Export to Excel/CSV

---

## Testing Checklist

### Database
- [ ] Migration runs successfully
- [ ] Foreign keys work correctly
- [ ] Indexes are created
- [ ] `duration` column changed from INTEGER to DATE
- [ ] Existing package durations converted to dates

### Backend
- [ ] Expiry date uses `$package->duration` directly (no calculation needed)
- [ ] Full payment assignment works
- [ ] Partial payment assignment works
- [ ] Additional payment recording works
- [ ] Payment status updates correctly
- [ ] Due payment detection works
- [ ] Statistics calculations are accurate

### Frontend
- [ ] Assignment modal shows/hides fields correctly
- [ ] Form validation works
- [ ] Payment history page loads
- [ ] Filters work (All, Paid, Partial, Due)
- [ ] Search functionality works
- [ ] View details modal works
- [ ] Record payment modal works
- [ ] Pagination works
- [ ] Sidebar navigation shows payment history
- [ ] Package create/edit forms show date picker for "Duration"
- [ ] Date picker doesn't allow past dates
- [ ] Package display shows "Duration: [Date]" format
- [ ] Partial payment expiry date cannot exceed package's duration date

### Integration
- [ ] Payment history syncs with subscription
- [ ] Expiry dates are set correctly
- [ ] Full payment expiry: uses package's `duration` date
- [ ] Partial payment expiry: custom date selected by admin (cannot exceed package's duration)
- [ ] User cannot access subscription after package's duration date
- [ ] Due status updates automatically
- [ ] Export functionality works

---

## Summary

This feature adds comprehensive payment tracking to the subscription system:

✅ **Full/Partial Payment Options** - Flexible payment terms
✅ **Payment History Table** - Complete payment records
✅ **Payment Status Tracking** - Paid, Partial, Due
✅ **Custom Expiry & Due Dates** - Admin control
✅ **Additional Payments** - Record subsequent payments
✅ **Payment Analytics** - Revenue and due tracking
✅ **Admin Dashboard Integration** - Easy access from sidebar
✅ **Date-Based Package Duration** - Duration field changed from integer to DATE

This system provides complete financial visibility and control over subscription payments.

---

## Important Notes

### Package Duration Change (Integer → Date)

**Major Change: `duration` field changed from INTEGER to DATE**

#### What Changed:
- **Before:** `duration` field (integer) - stored number of days
- **After:** `duration` field (DATE) - stores actual expiry date

#### Migration Impact:
```sql
-- Old structure
duration: 30 (integer representing days)

-- New structure
duration: '2026-12-31' (DATE - actual expiry date)
```

#### How It Works:
1. **Package Creation/Edit:**
   - Admin selects a date using date picker
   - Field: "Duration"
   - Example: December 31, 2026
   - No duration calculation needed

2. **User Assignment (Full Payment):**
   - User expiry date = Package's `duration` date
   - No calculation: `$expiryDate = $package->duration`

3. **User Assignment (Partial Payment):**
   - Admin can select custom expiry date
   - Cannot exceed package's `duration` date
   - Validation: `custom_date <= package.duration`

#### Benefits:
- ✅ More flexible - set any specific date
- ✅ No calculation errors
- ✅ Easier to set seasonal packages (e.g., "Valid until end of year")
- ✅ Clearer for admins to understand
- ✅ Can set long-term packages (e.g., valid until 2030)
- ✅ Field name stays the same (`duration`)

#### Code Updates Required:
```php
// Before
$expiryDate = now()->addDays($package->duration);

// After
$expiryDate = $package->duration;  // Now it's a date, not integer
```

#### UI Updates Required:
```vue
<!-- Before -->
<input type="number" v-model="form.duration" placeholder="30" />
<span>{{ package.duration }} days</span>

<!-- After -->
<input type="date" v-model="form.duration" :min="tomorrow" />
<span>Duration: {{ formatDate(package.duration) }}</span>
```
