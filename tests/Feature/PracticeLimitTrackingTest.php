<?php

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->student = User::factory()->create(['role' => 'student']);
    $this->admin = User::factory()->create(['role' => 'admin']);
});

describe('Writing Practice Limit Tracking', function () {
    it('allows access to writing practice page when user has remaining attempts', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => 5,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceWriting: 2)
            ->create();

        $response = actingAs($this->student)->get('/writing');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Writing/Index')
            ->has('subscription')
            ->where('subscription.practice_writing_enabled', true)
            ->where('subscription.practice_writing_limit', 5)
            ->where('subscription.practice_writing_used', 2)
            ->where('subscription.practice_writing_remaining', 3)
        );
    });

    it('blocks access to writing practice page when limit is reached', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => 5,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceWriting: 5)
            ->create();

        $response = actingAs($this->student)->get('/writing');

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');
    });

    it('blocks writing practice evaluation when limit is reached', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => 3,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceWriting: 3)
            ->create();

        $response = actingAs($this->student)
            ->postJson('/api/writing/practice-evaluate', [
                'writing' => [
                    [
                        'q' => 'Describe a graph showing...',
                        'ans' => str_repeat('This is a test answer with enough words. ', 15),
                    ],
                ],
            ]);

        $response->assertForbidden();
    });

    it('blocks access to writing practice when module is disabled', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => false,
            'practice_writing_limit' => null,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create();

        $response = actingAs($this->student)->get('/writing');

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');
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

        $response = actingAs($this->student)->get('/writing');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('subscription.practice_writing_enabled', true)
            ->where('subscription.practice_writing_limit', null)
            ->where('subscription.practice_writing_remaining', null)
        );
    });

    it('blocks access to writing practice when subscription is expired', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => 10,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->expired()
            ->create();

        $response = actingAs($this->student)->get('/writing');

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');
    });
});

describe('Speaking Practice Limit Tracking', function () {
    it('allows access to speaking practice page when user has remaining attempts', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_speaking_enabled' => true,
            'practice_speaking_limit' => 10,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceSpeaking: 4)
            ->create();

        $response = actingAs($this->student)->get('/speaking');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Speaking/Index')
            ->has('subscription')
            ->where('subscription.practice_speaking_enabled', true)
            ->where('subscription.practice_speaking_limit', 10)
            ->where('subscription.practice_speaking_used', 4)
            ->where('subscription.practice_speaking_remaining', 6)
        );
    });

    it('blocks access to speaking practice page when limit is reached', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_speaking_enabled' => true,
            'practice_speaking_limit' => 5,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceSpeaking: 5)
            ->create();

        $response = actingAs($this->student)->get('/speaking');

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');
    });

    it('blocks speaking practice start when limit is reached', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_speaking_enabled' => true,
            'practice_speaking_limit' => 3,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceSpeaking: 3)
            ->create();

        $response = actingAs($this->student)
            ->postJson('/api/speaking/start-session', [
                'part' => '1',
            ]);

        $response->assertForbidden();
    });

    it('blocks access to speaking practice when module is disabled', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_speaking_enabled' => false,
            'practice_speaking_limit' => null,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create();

        $response = actingAs($this->student)->get('/speaking');

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');
    });

    it('allows unlimited speaking practice when limit is null', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_speaking_enabled' => true,
            'practice_speaking_limit' => null,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceSpeaking: 50)
            ->create();

        $response = actingAs($this->student)->get('/speaking');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('subscription.practice_speaking_enabled', true)
            ->where('subscription.practice_speaking_limit', null)
            ->where('subscription.practice_speaking_remaining', null)
        );
    });
});

describe('Admin Bypass', function () {
    it('allows admin to access writing practice without subscription', function () {
        $response = actingAs($this->admin)->get('/writing');

        $response->assertSuccessful();
    });

    it('allows admin to access speaking practice without subscription', function () {
        $response = actingAs($this->admin)->get('/speaking');

        $response->assertSuccessful();
    });
});

describe('No Subscription Handling', function () {
    it('blocks writing practice when user has no subscription', function () {
        $response = actingAs($this->student)->get('/writing');

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');
    });

    it('blocks speaking practice when user has no subscription', function () {
        $response = actingAs($this->student)->get('/speaking');

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');
    });
});

describe('Dashboard Subscription Display', function () {
    it('displays writing and speaking practice limits on student dashboard', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_writing_enabled' => true,
            'practice_writing_limit' => 15,
            'practice_speaking_enabled' => true,
            'practice_speaking_limit' => 10,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->withUsage(practiceWriting: 8, practiceSpeaking: 3)
            ->create();

        $response = actingAs($this->student)->get('/student/dashboard');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('subscription')
            ->where('subscription.practice_modules.writing.enabled', true)
            ->where('subscription.practice_modules.writing.limit', 15)
            ->where('subscription.practice_modules.writing.used', 8)
            ->where('subscription.practice_modules.writing.remaining', 7)
            ->where('subscription.practice_modules.speaking.enabled', true)
            ->where('subscription.practice_modules.speaking.limit', 10)
            ->where('subscription.practice_modules.speaking.used', 3)
            ->where('subscription.practice_modules.speaking.remaining', 7)
        );
    });

    it('shows unlimited for reading and listening practice', function () {
        $package = SubscriptionPackage::factory()->create([
            'practice_reading_enabled' => true,
            'practice_listening_enabled' => true,
        ]);

        UserSubscription::factory()
            ->for($this->student)
            ->for($package)
            ->active()
            ->create();

        $response = actingAs($this->student)->get('/student/dashboard');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('subscription')
            ->where('subscription.practice_modules.reading.enabled', true)
            ->where('subscription.practice_modules.reading.unlimited', true)
            ->where('subscription.practice_modules.listening.enabled', true)
            ->where('subscription.practice_modules.listening.unlimited', true)
        );
    });
});
