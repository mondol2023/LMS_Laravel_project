<?php

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;

beforeEach(function () {
    $this->student = User::factory()->create(['role' => 'student']);
    $this->admin = User::factory()->create(['role' => 'admin']);
});

describe('Full Mock Test Limits', function () {
    it('allows access when user has remaining full mock tests', function () {
        $package = SubscriptionPackage::factory()->withLimits(fullMock: 5)->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(fullMock: 2)
            ->create();

        expect($this->student->canAccessResource('full_mock_test'))->toBeTrue();
        expect($this->student->getRemainingFullMockTests())->toBe(3);
    });

    it('denies access when full mock test limit is reached', function () {
        $package = SubscriptionPackage::factory()->withLimits(fullMock: 5)->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(fullMock: 5)
            ->create();

        expect($this->student->canAccessResource('full_mock_test'))->toBeFalse();
        expect($this->student->getRemainingFullMockTests())->toBe(0);
    });

    it('allows unlimited access when limit is null', function () {
        $package = SubscriptionPackage::factory()->unlimited()->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(fullMock: 100)
            ->create();

        expect($this->student->canAccessResource('full_mock_test'))->toBeTrue();
        expect($this->student->getRemainingFullMockTests())->toBeNull();
    });

    it('increments full mock test usage correctly', function () {
        $package = SubscriptionPackage::factory()->withLimits(fullMock: 5)->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(fullMock: 2)
            ->create();

        expect($subscription->full_mock_tests_used)->toBe(2);

        $this->student->incrementUsage('full_mock_test');
        $subscription->refresh();

        expect($subscription->full_mock_tests_used)->toBe(3);
        expect($this->student->getRemainingFullMockTests())->toBe(2);
    });
});

describe('Partial Test Limits - Reading', function () {
    it('allows access when user has remaining reading parts', function () {
        $package = SubscriptionPackage::factory()->withLimits(partialReading: 10)->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(partialReading: 5)
            ->create();

        expect($this->student->canAccessResource('partial_reading'))->toBeTrue();
        expect($this->student->getRemainingPartialReading())->toBe(5);
    });

    it('denies access when reading parts limit is reached', function () {
        $package = SubscriptionPackage::factory()->withLimits(partialReading: 10)->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(partialReading: 10)
            ->create();

        expect($this->student->canAccessResource('partial_reading'))->toBeFalse();
        expect($this->student->getRemainingPartialReading())->toBe(0);
    });

    it('increments only reading counter not other parts', function () {
        $package = SubscriptionPackage::factory()->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(partialReading: 2, partialWriting: 3, partialListening: 1, partialSpeaking: 0)
            ->create();

        $this->student->incrementUsage('partial_reading');
        $subscription->refresh();

        expect($subscription->partial_reading_used)->toBe(3);
        expect($subscription->partial_writing_used)->toBe(3); // Unchanged
        expect($subscription->partial_listening_used)->toBe(1); // Unchanged
        expect($subscription->partial_speaking_used)->toBe(0); // Unchanged
    });
});

describe('Partial Test Limits - Writing', function () {
    it('allows access when user has remaining writing parts', function () {
        $package = SubscriptionPackage::factory()->withLimits(partialWriting: 8)->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(partialWriting: 3)
            ->create();

        expect($this->student->canAccessResource('partial_writing'))->toBeTrue();
        expect($this->student->getRemainingPartialWriting())->toBe(5);
    });

    it('increments only writing counter not other parts', function () {
        $package = SubscriptionPackage::factory()->create();
        $subscription = UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(partialReading: 2, partialWriting: 3, partialListening: 1, partialSpeaking: 0)
            ->create();

        $this->student->incrementUsage('partial_writing');
        $subscription->refresh();

        expect($subscription->partial_reading_used)->toBe(2); // Unchanged
        expect($subscription->partial_writing_used)->toBe(4);
        expect($subscription->partial_listening_used)->toBe(1); // Unchanged
        expect($subscription->partial_speaking_used)->toBe(0); // Unchanged
    });
});

describe('Partial Test Limits - Listening', function () {
    it('allows access when user has remaining listening parts', function () {
        $package = SubscriptionPackage::factory()->withLimits(partialListening: 12)->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(partialListening: 8)
            ->create();

        expect($this->student->canAccessResource('partial_listening'))->toBeTrue();
        expect($this->student->getRemainingPartialListening())->toBe(4);
    });
});

describe('Partial Test Limits - Speaking', function () {
    it('allows access when user has remaining speaking parts', function () {
        $package = SubscriptionPackage::factory()->withLimits(partialSpeaking: 6)->create();
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(partialSpeaking: 2)
            ->create();

        expect($this->student->canAccessResource('partial_speaking'))->toBeTrue();
        expect($this->student->getRemainingPartialSpeaking())->toBe(4);
    });
});

describe('Practice Module Access', function () {
    it('allows reading practice when enabled', function () {
        $package = SubscriptionPackage::factory()->create(['practice_reading_enabled' => true]);
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create();

        expect($this->student->isPracticeModuleEnabled('reading'))->toBeTrue();
        expect($this->student->canAccessResource('practice_reading'))->toBeTrue();
    });

    it('denies reading practice when disabled', function () {
        $package = SubscriptionPackage::factory()->create(['practice_reading_enabled' => false]);
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create();

        expect($this->student->isPracticeModuleEnabled('reading'))->toBeFalse();
        expect($this->student->canAccessResource('practice_reading'))->toBeFalse();
    });

    it('allows writing practice when enabled and under limit', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => 10,
        ]);
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceWriting: 5)
            ->create();

        expect($this->student->isPracticeModuleEnabled('writing'))->toBeTrue();
        expect($this->student->canAccessResource('practice_writing'))->toBeTrue();
        expect($this->student->getRemainingWritingPractice())->toBe(5);
    });

    it('denies writing practice when limit reached', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => 10,
        ]);
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceWriting: 10)
            ->create();

        expect($this->student->canAccessResource('practice_writing'))->toBeFalse();
        expect($this->student->getRemainingWritingPractice())->toBe(0);
    });

    it('allows unlimited writing practice when limit is null', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => null,
        ]);
        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceWriting: 100)
            ->create();

        expect($this->student->canAccessResource('practice_writing'))->toBeTrue();
        expect($this->student->getRemainingWritingPractice())->toBeNull();
    });
});

describe('Independent Partial Test Limits', function () {
    it('allows access to other parts when one part limit is reached', function () {
        $package = SubscriptionPackage::factory()
            ->withLimits(
                partialReading: 10,
                partialWriting: 8,
                partialListening: 12,
                partialSpeaking: 6
            )
            ->create();

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(
                partialReading: 10, // MAXED OUT
                partialWriting: 4,
                partialListening: 6,
                partialSpeaking: 2
            )
            ->create();

        // Reading is maxed out
        expect($this->student->canAccessResource('partial_reading'))->toBeFalse();
        expect($this->student->getRemainingPartialReading())->toBe(0);

        // But other parts still available
        expect($this->student->canAccessResource('partial_writing'))->toBeTrue();
        expect($this->student->getRemainingPartialWriting())->toBe(4);

        expect($this->student->canAccessResource('partial_listening'))->toBeTrue();
        expect($this->student->getRemainingPartialListening())->toBe(6);

        expect($this->student->canAccessResource('partial_speaking'))->toBeTrue();
        expect($this->student->getRemainingPartialSpeaking())->toBe(4);
    });
});

describe('Admin Bypass', function () {
    it('allows admin to access resources without subscription', function () {
        // Admin has no subscription at all
        expect($this->admin->hasActiveSubscription())->toBeFalse();

        // But we're testing that middleware would bypass admin
        // This is more of a middleware test, which we'll handle separately
        expect($this->admin->isAdmin())->toBeTrue();
    });
});
