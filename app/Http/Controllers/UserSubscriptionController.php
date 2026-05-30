<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignSubscriptionRequest;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserSubscriptionController extends Controller
{
    public function show(User $user): Response
    {
        $user->load([
            'activeSubscription.package',
            'subscriptions.package',
            'subscriptionHistory.oldPackage',
            'subscriptionHistory.newPackage',
            'subscriptionHistory.performedBy',
        ]);

        $availablePackages = SubscriptionPackage::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return Inertia::render('Subscriptions/UserSubscription', [
            'user' => $user,
            'availablePackages' => $availablePackages,
        ]);
    }

    public function assign(AssignSubscriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->findOrFail($validated['user_id']);
        $package = SubscriptionPackage::query()->findOrFail($validated['package_id']);

        // Deactivate any existing active subscriptions
        $oldSubscription = $user->activeSubscription;
        if ($oldSubscription) {
            $oldSubscription->update(['is_active' => false]);
        }

        // Calculate dates
        $startDate = $validated['start_date'] ?? now();
        $expiresAt = now()->parse($startDate)->addDays($package->duration);

        // Create new subscription
        $subscription = UserSubscription::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'subscribed_at' => $startDate,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        // Log in history
        SubscriptionHistory::query()->create([
            'user_id' => $user->id,
            'old_package_id' => $oldSubscription?->package_id,
            'new_package_id' => $package->id,
            'action' => $oldSubscription ? 'upgraded' : 'assigned',
            'performed_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Subscription assigned successfully to {$user->name}.");
    }

    public function upgrade(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'package_id' => ['required', 'exists:subscription_packages,id'],
            'reset_counters' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $currentSubscription = $user->activeSubscription;
        $newPackage = SubscriptionPackage::query()->findOrFail($validated['package_id']);

        if (! $currentSubscription) {
            return back()->withErrors(['error' => 'User has no active subscription.']);
        }

        // Deactivate current subscription
        $currentSubscription->update(['is_active' => false]);

        // Create new subscription
        $subscription = UserSubscription::query()->create([
            'user_id' => $user->id,
            'package_id' => $newPackage->id,
            'subscribed_at' => now(),
            'expires_at' => now()->addDays($newPackage->duration),
            'is_active' => true,
            // Optionally carry forward usage if not resetting
            'full_mock_tests_used' => $validated['reset_counters'] ? 0 : $currentSubscription->full_mock_tests_used,
            'partial_reading_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_reading_used,
            'partial_writing_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_writing_used,
            'partial_listening_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_listening_used,
            'partial_speaking_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_speaking_used,
            'practice_writing_used' => $validated['reset_counters'] ? 0 : $currentSubscription->practice_writing_used,
            'practice_speaking_used' => $validated['reset_counters'] ? 0 : $currentSubscription->practice_speaking_used,
        ]);

        // Log in history
        SubscriptionHistory::query()->create([
            'user_id' => $user->id,
            'old_package_id' => $currentSubscription->package_id,
            'new_package_id' => $newPackage->id,
            'action' => 'upgraded',
            'performed_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Subscription upgraded successfully for {$user->name}.");
    }

    public function renew(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'reset_counters' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $currentSubscription = $user->activeSubscription;

        if (! $currentSubscription) {
            return back()->withErrors(['error' => 'User has no active subscription to renew.']);
        }

        $package = $currentSubscription->package;

        // Deactivate current subscription
        $currentSubscription->update(['is_active' => false]);

        // Create renewed subscription
        $newExpiry = $currentSubscription->expires_at->isPast()
            ? now()->addDays($package->duration)
            : $currentSubscription->expires_at->addDays($package->duration);

        $subscription = UserSubscription::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'subscribed_at' => now(),
            'expires_at' => $newExpiry,
            'is_active' => true,
            // Reset or carry forward based on preference
            'full_mock_tests_used' => $validated['reset_counters'] ? 0 : $currentSubscription->full_mock_tests_used,
            'partial_reading_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_reading_used,
            'partial_writing_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_writing_used,
            'partial_listening_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_listening_used,
            'partial_speaking_used' => $validated['reset_counters'] ? 0 : $currentSubscription->partial_speaking_used,
            'practice_writing_used' => $validated['reset_counters'] ? 0 : $currentSubscription->practice_writing_used,
            'practice_speaking_used' => $validated['reset_counters'] ? 0 : $currentSubscription->practice_speaking_used,
        ]);

        // Log in history
        SubscriptionHistory::query()->create([
            'user_id' => $user->id,
            'old_package_id' => $package->id,
            'new_package_id' => $package->id,
            'action' => 'renewed',
            'performed_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Subscription renewed successfully for {$user->name}.");
    }

    public function cancel(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'immediate' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription = $user->activeSubscription;

        if (! $subscription) {
            return back()->withErrors(['error' => 'User has no active subscription to cancel.']);
        }

        if ($validated['immediate'] ?? true) {
            $subscription->update(['is_active' => false]);
        }

        // Log in history
        SubscriptionHistory::query()->create([
            'user_id' => $user->id,
            'old_package_id' => $subscription->package_id,
            'new_package_id' => null,
            'action' => 'cancelled',
            'performed_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $timing = ($validated['immediate'] ?? true) ? 'immediately' : 'at expiry';

        return back()->with('success', "Subscription cancelled {$timing} for {$user->name}.");
    }
}
