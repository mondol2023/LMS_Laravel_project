<?php

namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionUsersController extends Controller
{
    public function index(Request $request): Response
    {
        $packageId = $request->get('package');
        $search = $request->get('search');
        $filter = $request->get('filter', $packageId ? 'assigned' : 'all');

        $usersQuery = User::query()
            ->with(['activeSubscription.package', 'activeSubscription.payment'])
            ->where('role', 'student');

        // Apply filter based on package context
        if ($packageId) {
            // When a specific package is selected
            if ($filter === 'assigned') {
                // Show only users assigned to THIS package
                $usersQuery->whereHas('activeSubscription', function ($query) use ($packageId) {
                    $query->where('is_active', true)
                        ->where('package_id', $packageId);
                });
            } else {
                // Show ALL users, but order by: assigned to this package first, then others
                $usersQuery->orderByRaw('
                    CASE
                        WHEN EXISTS (
                            SELECT 1 FROM user_subscriptions
                            WHERE user_subscriptions.user_id = users.id
                            AND user_subscriptions.package_id = ?
                            AND user_subscriptions.is_active = 1
                        ) THEN 0
                        ELSE 1
                    END
                ', [$packageId]);
            }
        } else {
            // When no specific package (general user management)
            if ($filter === 'assigned') {
                $usersQuery->whereHas('activeSubscription', function ($query) {
                    $query->where('is_active', true);
                });
            } elseif ($filter === 'unassigned') {
                $usersQuery->whereDoesntHave('activeSubscription', function ($query) {
                    $query->where('is_active', true);
                });
            }
        }

        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $usersQuery->paginate(20)->through(function ($user) use ($packageId) {
            $activeSubscription = $user->activeSubscription;
            $isAssignedToThisPackage = $activeSubscription && $activeSubscription->package_id == $packageId;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roll_number' => $user->roll_number,
                'phone' => $user->phone,
                'subscription' => $activeSubscription ? [
                    'id' => $activeSubscription->id,
                    'package' => [
                        'id' => $activeSubscription->package->id,
                        'name' => $activeSubscription->package->name,
                        'duration' => $activeSubscription->package->duration,
                    ],
                    'subscribed_at' => $activeSubscription->subscribed_at,
                    'expires_at' => $activeSubscription->expires_at,
                    'days_remaining' => $activeSubscription->days_remaining,
                    'is_active' => $activeSubscription->isActive(),
                    'payment' => $activeSubscription->payment ? [
                        'id' => $activeSubscription->payment->id,
                        'payment_status' => $activeSubscription->payment->payment_status,
                        'paid_amount' => $activeSubscription->payment->paid_amount,
                        'due_amount' => $activeSubscription->payment->due_amount,
                        'payment_date' => $activeSubscription->payment->payment_date,
                        'due_date' => $activeSubscription->payment->due_date,
                        'package_price' => $activeSubscription->payment->package_price,
                    ] : null,
                ] : null,
                'is_assigned_to_selected_package' => $isAssignedToThisPackage,
            ];
        });

        $packages = SubscriptionPackage::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration', 'price']);

        return Inertia::render('Subscriptions/Users/Index', [
            'users' => $users,
            'packages' => $packages,
            'selectedPackage' => $packageId,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function store(Request $request)
    {
        // First get the package to use in validation
        $package = SubscriptionPackage::findOrFail($request->package_id);

        // Check if user has existing active subscription (renewal scenario)
        $existingSubscription = UserSubscription::query()
            ->where('user_id', $request->user_id)
            ->where('is_active', true)
            ->first();

        // For renewals, calculate max from current expiry; for new assignments, from today
        if ($existingSubscription && $existingSubscription->expires_at) {
            $maxExpiryDate = \Carbon\Carbon::parse($existingSubscription->expires_at)
                ->addMonths($package->duration)
                ->toDateString();
        } else {
            $maxExpiryDate = now()->addMonths($package->duration)->toDateString();
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'package_id' => ['required', 'exists:subscription_packages,id'],
            'payment_type' => ['required', 'in:full,partial'],
            'paid_amount' => ['required_if:payment_type,partial', 'numeric', 'min:0'],
            'expiry_date' => [
                'nullable',
                'date',
                'after:today',
                'before_or_equal:'.$maxExpiryDate,
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

        // Use provided expiry date if available (for renewals), otherwise calculate from today
        $expiryDate = !empty($validated['expiry_date'])
            ? $validated['expiry_date']
            : now()->addMonths($package->duration)->toDateString();

        // Get existing active subscription for history
        $oldSubscription = UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        // Delete existing subscriptions (clean approach)
        UserSubscription::query()
            ->where('user_id', $user->id)
            ->delete();

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
            'old_package_id' => $oldSubscription?->package_id,
            'new_package_id' => $package->id,
            'action' => $oldSubscription ? 'upgraded' : 'assigned',
            'performed_by' => Auth::id(),
            'notes' => $oldSubscription
                ? "Changed from package {$oldSubscription->package->name} to {$package->name} with {$validated['payment_type']} payment (BDT {$paidAmount})"
                : "Assigned to package {$package->name} with {$validated['payment_type']} payment (BDT {$paidAmount})",
        ]);

        return redirect()->back()->with('success', 'Subscription assigned and payment recorded successfully.');
    }

    public function destroy(UserSubscription $subscription)
    {
        $user = $subscription->user;
        $package = $subscription->package;

        // Delete the subscription
        $subscription->delete();

        // Log subscription history
        SubscriptionHistory::create([
            'user_id' => $user->id,
            'old_package_id' => $package->id,
            'new_package_id' => null,
            'action' => 'cancelled',
            'performed_by' => Auth::id(),
            'notes' => "Removed from package {$package->name}",
        ]);

        return redirect()->back()->with('success', 'Subscription removed successfully.');
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'package_id' => ['required', 'exists:subscription_packages,id'],
        ]);

        $package = SubscriptionPackage::findOrFail($validated['package_id']);

        foreach ($validated['user_ids'] as $userId) {
            // Get existing active subscription for history
            $oldSubscription = UserSubscription::query()
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            // Delete existing subscriptions
            UserSubscription::query()
                ->where('user_id', $userId)
                ->delete();

            $expiryDate = now()->addMonths($package->duration)->toDateString();

            // Create new subscription
            $subscription = UserSubscription::create([
                'user_id' => $userId,
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

            // Create payment record (full payment for bulk assign)
            $payment = PaymentHistory::create([
                'user_id' => $userId,
                'subscription_id' => $subscription->id,
                'package_id' => $package->id,
                'package_price' => $package->price,
                'paid_amount' => $package->price,
                'due_amount' => 0,
                'payment_status' => 'paid',
                'payment_date' => now(),
                'due_date' => null,
                'expiry_date' => $expiryDate,
                'payment_method' => 'Bulk Assignment',
                'transaction_note' => 'Bulk assignment - full payment',
                'assigned_by' => Auth::id(),
            ]);

            // Link payment to subscription
            $subscription->update(['payment_id' => $payment->id]);

            // Log subscription history
            SubscriptionHistory::create([
                'user_id' => $userId,
                'old_package_id' => $oldSubscription?->package_id,
                'new_package_id' => $package->id,
                'action' => $oldSubscription ? 'upgraded' : 'assigned',
                'performed_by' => Auth::id(),
                'notes' => $oldSubscription
                    ? "Bulk action: Changed from package {$oldSubscription->package->name} to {$package->name}"
                    : "Bulk action: Assigned to package {$package->name}",
            ]);
        }

        return redirect()->back()->with('success', count($validated['user_ids']).' user(s) assigned to package successfully.');
    }

    public function bulkRemove(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'exists:users,id'],
        ]);

        $subscriptions = UserSubscription::query()
            ->whereIn('user_id', $validated['user_ids'])
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            $package = $subscription->package;

            // Delete the subscription
            $subscription->delete();

            // Log subscription history
            SubscriptionHistory::create([
                'user_id' => $user->id,
                'old_package_id' => $package->id,
                'new_package_id' => null,
                'action' => 'cancelled',
                'performed_by' => Auth::id(),
                'notes' => "Bulk action: Removed from package {$package->name}",
            ]);
        }

        return redirect()->back()->with('success', $subscriptions->count().' subscription(s) removed successfully.');
    }
}
