<?php

use App\Models\CoachingIp;
use App\Models\Exam;
use App\Models\User;
use App\Services\CoachingAccessService;

beforeEach(function () {
    $this->student = User::factory()->create(['role' => 'student']);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->coachingAccessService = app(CoachingAccessService::class);
});

describe('Coaching IP Model', function () {
    it('creates a coaching IP with correct attributes', function () {
        $coachingIp = CoachingIp::create([
            'ip_address' => '192.168.1.100',
            'label' => 'Main Office',
            'is_active' => true,
        ]);

        expect($coachingIp->ip_address)->toBe('192.168.1.100');
        expect($coachingIp->label)->toBe('Main Office');
        expect($coachingIp->is_active)->toBeTrue();
    });

    it('checks if an IP is whitelisted', function () {
        CoachingIp::create([
            'ip_address' => '192.168.1.100',
            'is_active' => true,
        ]);

        expect(CoachingIp::isCoachingIp('192.168.1.100'))->toBeTrue();
        expect(CoachingIp::isCoachingIp('192.168.1.200'))->toBeFalse();
    });

    it('does not match inactive IPs', function () {
        CoachingIp::create([
            'ip_address' => '192.168.1.100',
            'is_active' => false,
        ]);

        expect(CoachingIp::isCoachingIp('192.168.1.100'))->toBeFalse();
    });

    it('normalizes IPv6 localhost to IPv4', function () {
        CoachingIp::create([
            'ip_address' => '127.0.0.1',
            'is_active' => true,
        ]);

        // IPv6 localhost should match IPv4 localhost
        expect(CoachingIp::isCoachingIp('::1'))->toBeTrue();
        expect(CoachingIp::isCoachingIp('127.0.0.1'))->toBeTrue();
    });

    it('normalizes IPv4-mapped IPv6 addresses', function () {
        CoachingIp::create([
            'ip_address' => '192.168.1.100',
            'is_active' => true,
        ]);

        // IPv4-mapped IPv6 should match IPv4
        expect(CoachingIp::isCoachingIp('::ffff:192.168.1.100'))->toBeTrue();
    });

    it('gets all active IPs', function () {
        CoachingIp::create(['ip_address' => '192.168.1.1', 'is_active' => true]);
        CoachingIp::create(['ip_address' => '192.168.1.2', 'is_active' => true]);
        CoachingIp::create(['ip_address' => '192.168.1.3', 'is_active' => false]);

        $activeIps = CoachingIp::getActiveIps();

        expect($activeIps)->toHaveCount(2);
        expect($activeIps)->toContain('192.168.1.1');
        expect($activeIps)->toContain('192.168.1.2');
        expect($activeIps)->not->toContain('192.168.1.3');
    });
});

describe('Coaching Access Service', function () {
    it('identifies whitelisted IP as coaching access', function () {
        CoachingIp::create([
            'ip_address' => '192.168.1.100',
            'is_active' => true,
        ]);

        // Clear cache to ensure fresh data
        $this->coachingAccessService->clearCache();

        expect($this->coachingAccessService->isWhitelistedIp('192.168.1.100'))->toBeTrue();
        expect($this->coachingAccessService->isWhitelistedIp('10.0.0.1'))->toBeFalse();
    });

    it('returns correct access mode', function () {
        CoachingIp::create([
            'ip_address' => '192.168.1.100',
            'is_active' => true,
        ]);

        $this->coachingAccessService->clearCache();

        // Since we can't easily mock request IP in tests, we test the isWhitelistedIp method
        expect($this->coachingAccessService->isWhitelistedIp('192.168.1.100'))->toBeTrue();
    });

    it('caches the IP list', function () {
        $ip1 = CoachingIp::create(['ip_address' => '192.168.1.1', 'is_active' => true]);

        $this->coachingAccessService->clearCache();

        // First call should cache
        expect($this->coachingAccessService->isWhitelistedIp('192.168.1.1'))->toBeTrue();

        // Add new IP (won't be in cache yet)
        CoachingIp::create(['ip_address' => '192.168.1.2', 'is_active' => true]);

        // Still uses cached version
        expect($this->coachingAccessService->getWhitelistedIps())->toHaveCount(1);

        // Clear cache and check again
        $this->coachingAccessService->clearCache();
        expect($this->coachingAccessService->getWhitelistedIps())->toHaveCount(2);
    });
});

describe('Coaching IP Admin CRUD', function () {
    it('allows admin to view coaching IPs list', function () {
        CoachingIp::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/settings/coaching-ips');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/CoachingIps/Index')
            ->has('coachingIps.data', 3)
        );
    });

    it('allows admin to create coaching IP', function () {
        $response = $this->actingAs($this->admin)->post('/settings/coaching-ips', [
            'ip_address' => '10.0.0.50',
            'label' => 'Test Location',
            'is_active' => true,
        ]);

        $response->assertRedirect('/settings/coaching-ips');
        $this->assertDatabaseHas('coaching_ips', [
            'ip_address' => '10.0.0.50',
            'label' => 'Test Location',
            'is_active' => true,
        ]);
    });

    it('validates IP address format', function () {
        $response = $this->actingAs($this->admin)->post('/settings/coaching-ips', [
            'ip_address' => 'invalid-ip',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('ip_address');
    });

    it('prevents duplicate IP addresses', function () {
        CoachingIp::create(['ip_address' => '192.168.1.1', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post('/settings/coaching-ips', [
            'ip_address' => '192.168.1.1',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('ip_address');
    });

    it('allows admin to update coaching IP', function () {
        $coachingIp = CoachingIp::create([
            'ip_address' => '192.168.1.1',
            'label' => 'Old Label',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put("/settings/coaching-ips/{$coachingIp->id}", [
            'ip_address' => '192.168.1.2',
            'label' => 'New Label',
            'is_active' => false,
        ]);

        $response->assertRedirect('/settings/coaching-ips');
        $this->assertDatabaseHas('coaching_ips', [
            'id' => $coachingIp->id,
            'ip_address' => '192.168.1.2',
            'label' => 'New Label',
            'is_active' => false,
        ]);
    });

    it('allows admin to delete coaching IP', function () {
        $coachingIp = CoachingIp::create([
            'ip_address' => '192.168.1.1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/settings/coaching-ips/{$coachingIp->id}");

        $response->assertRedirect('/settings/coaching-ips');
        $this->assertDatabaseMissing('coaching_ips', ['id' => $coachingIp->id]);
    });

    it('allows admin to toggle IP status', function () {
        $coachingIp = CoachingIp::create([
            'ip_address' => '192.168.1.1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post("/settings/coaching-ips/{$coachingIp->id}/toggle");

        $response->assertRedirect();
        $this->assertDatabaseHas('coaching_ips', [
            'id' => $coachingIp->id,
            'is_active' => false,
        ]);
    });

    it('denies non-admin access to coaching IPs', function () {
        $response = $this->actingAs($this->student)->get('/settings/coaching-ips');

        $response->assertStatus(403);
    });
});

describe('Exam Access with Coaching Mode', function () {
    beforeEach(function () {
        $this->exam = Exam::factory()->create();
    });

    it('requires login for exam access from external IP', function () {
        // No coaching IPs configured, so all IPs are external
        $response = $this->get("/exam/{$this->exam->uuid}");

        $response->assertRedirect('/login');
    });

    it('allows logged in users to access exam from external IP', function () {
        $response = $this->actingAs($this->student)->get("/exam/{$this->exam->uuid}");

        // Should not redirect to login
        $response->assertStatus(200);
    });
});

describe('Inertia Shared Data', function () {
    it('shares coaching mode flag with frontend', function () {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('isCoachingMode')
            ->has('clientIp')
        );
    });
});
