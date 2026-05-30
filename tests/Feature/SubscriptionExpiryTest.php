<?php

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;

beforeEach(function () {
    $this->student = User::factory()->create(['role' => 'student']);
});

describe('Subscription Expiry Check', function () {
    it('identifies active subscription as not expired', function () {
        $package = SubscriptionPackage::factory()->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create([
                'subscribed_at' => now()->subDays(10),
                'expires_at' => now()->addDays(20),
            ]);

        expect($this->student->hasActiveSubscription())->toBeTrue();
        expect($this->student->isSubscriptionExpired())->toBeFalse();
    });

    it('identifies expired subscription', function () {
        $package = SubscriptionPackage::factory()->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->expired()
            ->create();

        expect($this->student->isSubscriptionExpired())->toBeTrue();
    });

    it('calculates days remaining correctly', function () {
        $package = SubscriptionPackage::factory()->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create([
                'subscribed_at' => now()->subDays(5),
                'expires_at' => now()->addDays(25),
            ]);

        expect($subscription->days_remaining)->toBe(25);
    });

    it('shows zero days for expired subscription', function () {
        $package = SubscriptionPackage::factory()->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->create([
                'subscribed_at' => now()->subDays(40),
                'expires_at' => now()->subDays(10),
                'is_active' => true,
            ]);

        expect($subscription->days_remaining)->toBe(0);
        expect($subscription->isExpired())->toBeTrue();
    });

    it('denies access to all resources when subscription is expired', function () {
        $package = SubscriptionPackage::factory()->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->expired()
            ->create();

        expect($this->student->canAccessResource('full_mock_test'))->toBeFalse();
        expect($this->student->canAccessResource('partial_reading'))->toBeFalse();
        expect($this->student->canAccessResource('partial_writing'))->toBeFalse();
        expect($this->student->canAccessResource('practice_reading'))->toBeFalse();
        expect($this->student->canAccessResource('practice_writing'))->toBeFalse();
    });

    it('returns zero remaining for all limits when subscription is expired', function () {
        $package = SubscriptionPackage::factory()->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->expired()
            ->create();

        expect($this->student->getRemainingFullMockTests())->toBe(0);
        expect($this->student->getRemainingPartialReading())->toBe(0);
        expect($this->student->getRemainingPartialWriting())->toBe(0);
        expect($this->student->getRemainingPartialListening())->toBe(0);
        expect($this->student->getRemainingPartialSpeaking())->toBe(0);
        expect($this->student->getRemainingWritingPractice())->toBe(0);
        expect($this->student->getRemainingSpeakingPractice())->toBe(0);
    });
});

describe('Subscription Expiring Soon', function () {
    it('identifies subscription expiring within 7 days', function () {
        $package = SubscriptionPackage::factory()->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->expiringSoon()
            ->create();

        expect($subscription->days_remaining)->toBeLessThanOrEqual(7);
        expect($subscription->days_remaining)->toBeGreaterThan(0);
        expect($subscription->isActive())->toBeTrue();
    });

    it('still allows access when subscription is expiring soon but not expired', function () {
        $package = SubscriptionPackage::factory()->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->expiringSoon()
            ->create();

        expect($this->student->hasActiveSubscription())->toBeTrue();
        expect($this->student->canAccessResource('full_mock_test'))->toBeTrue();
    });
});

describe('No Subscription', function () {
    it('identifies user without subscription', function () {
        expect($this->student->hasActiveSubscription())->toBeFalse();
        expect($this->student->activeSubscription)->toBeNull();
    });

    it('denies access to all resources when user has no subscription', function () {
        expect($this->student->canAccessResource('full_mock_test'))->toBeFalse();
        expect($this->student->canAccessResource('partial_reading'))->toBeFalse();
        expect($this->student->canAccessResource('practice_writing'))->toBeFalse();
    });

    it('returns zero remaining for all limits when user has no subscription', function () {
        expect($this->student->getRemainingFullMockTests())->toBe(0);
        expect($this->student->getRemainingPartialReading())->toBe(0);
        expect($this->student->getRemainingWritingPractice())->toBe(0);
    });
});

describe('Inactive Subscription', function () {
    it('treats inactive subscription as no subscription', function () {
        $package = SubscriptionPackage::factory()->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->inactive()
            ->create([
                'subscribed_at' => now()->subDays(5),
                'expires_at' => now()->addDays(25),
            ]);

        expect($this->student->hasActiveSubscription())->toBeFalse();
        expect($this->student->canAccessResource('full_mock_test'))->toBeFalse();
    });
});

describe('Subscription Status Method', function () {
    it('correctly identifies active subscription', function () {
        $package = SubscriptionPackage::factory()->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create();

        expect($subscription->isActive())->toBeTrue();
        expect($subscription->isExpired())->toBeFalse();
    });

    it('correctly identifies expired subscription', function () {
        $package = SubscriptionPackage::factory()->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->expired()
            ->create();

        expect($subscription->isExpired())->toBeTrue();
    });
});
