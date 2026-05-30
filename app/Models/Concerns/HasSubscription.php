<?php

namespace App\Models\Concerns;

use App\Models\SubscriptionHistory;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasSubscription
{
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)->where('is_active', true)->latest();
    }

    public function subscriptionHistory(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function hasActiveSubscription(): bool
    {
        $subscription = $this->activeSubscription;

        if (! $subscription) {
            return false;
        }

        return $subscription->isActive();
    }

    public function getRemainingFullMockTests(): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            return 0;
        }

        $limit = $subscription->package->full_mock_test_limit;

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $subscription->full_mock_tests_used);
    }

    // Partial Mock Tests - Separate methods for each part
    public function getRemainingPartialReading(): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            return 0;
        }

        $limit = $subscription->package->partial_reading_limit;

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $subscription->partial_reading_used);
    }

    public function getRemainingPartialWriting(): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            return 0;
        }

        $limit = $subscription->package->partial_writing_limit;

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $subscription->partial_writing_used);
    }

    public function getRemainingPartialListening(): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            return 0;
        }

        $limit = $subscription->package->partial_listening_limit;

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $subscription->partial_listening_used);
    }

    public function getRemainingPartialSpeaking(): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            return 0;
        }

        $limit = $subscription->package->partial_speaking_limit;

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $subscription->partial_speaking_used);
    }

    // Practice modules
    public function isPracticeModuleEnabled(string $module): bool
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            return false;
        }

        return match ($module) {
            'reading' => $subscription->package->practice_reading_enabled,
            'listening' => $subscription->package->practice_listening_enabled,
            'writing' => $subscription->package->practice_writing_enabled,
            'speaking' => $subscription->package->practice_speaking_enabled,
            default => false,
        };
    }

    public function getRemainingWritingPractice(): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive() || ! $subscription->package->practice_writing_enabled) {
            return 0;
        }

        $limit = $subscription->package->practice_writing_limit;

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $subscription->practice_writing_used);
    }

    public function getRemainingSpeakingPractice(): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive() || ! $subscription->package->practice_speaking_enabled) {
            return 0;
        }

        $limit = $subscription->package->practice_speaking_limit;

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $subscription->practice_speaking_used);
    }

    // General access check
    public function canAccessResource(string $type, ?string $part = null): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        return match ($type) {
            'full_mock_test' => $this->getRemainingFullMockTests() === null || $this->getRemainingFullMockTests() > 0,
            'partial_reading' => $this->getRemainingPartialReading() === null || $this->getRemainingPartialReading() > 0,
            'partial_writing' => $this->getRemainingPartialWriting() === null || $this->getRemainingPartialWriting() > 0,
            'partial_listening' => $this->getRemainingPartialListening() === null || $this->getRemainingPartialListening() > 0,
            'partial_speaking' => $this->getRemainingPartialSpeaking() === null || $this->getRemainingPartialSpeaking() > 0,
            'practice_reading' => $this->isPracticeModuleEnabled('reading'),
            'practice_listening' => $this->isPracticeModuleEnabled('listening'),
            'practice_writing' => $this->isPracticeModuleEnabled('writing') && ($this->getRemainingWritingPractice() === null || $this->getRemainingWritingPractice() > 0),
            'practice_speaking' => $this->isPracticeModuleEnabled('speaking') && ($this->getRemainingSpeakingPractice() === null || $this->getRemainingSpeakingPractice() > 0),
            default => false,
        };
    }

    // Increment usage
    public function incrementUsage(string $type): void
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            return;
        }

        match ($type) {
            'full_mock_test' => $subscription->increment('full_mock_tests_used'),
            'partial_reading' => $subscription->increment('partial_reading_used'),
            'partial_writing' => $subscription->increment('partial_writing_used'),
            'partial_listening' => $subscription->increment('partial_listening_used'),
            'partial_speaking' => $subscription->increment('partial_speaking_used'),
            'practice_writing' => $subscription->increment('practice_writing_used'),
            'practice_speaking' => $subscription->increment('practice_speaking_used'),
            default => null,
        };
    }

    public function isSubscriptionExpired(): bool
    {
        $subscription = $this->activeSubscription;

        if (! $subscription) {
            return true;
        }

        return $subscription->isExpired();
    }
}
