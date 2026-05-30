<?php

namespace App\Services;

use App\Models\CoachingIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CoachingAccessService
{
    /**
     * Cache key for coaching IPs.
     */
    protected const CACHE_KEY = 'coaching_ips_list';

    /**
     * Cache duration in seconds (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Check if the current request is from a coaching center.
     */
    public function isCoachingAccess(?Request $request = null): bool
    {
        $request = $request ?? request();
        $clientIp = $this->getClientIp($request);

        return $this->isWhitelistedIp($clientIp);
    }

    /**
     * Get the client's IP address (normalized).
     */
    public function getClientIp(?Request $request = null): string
    {
        $request = $request ?? request();

        // Check for forwarded IP (behind proxy/load balancer)
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = array_map('trim', explode(',', $forwardedFor));
            $ip = $ips[0];
        } else {
            $ip = $request->ip() ?? '0.0.0.0';
        }

        return $this->normalizeIp($ip);
    }

    /**
     * Normalize IP address (handle IPv6 localhost, etc.)
     */
    protected function normalizeIp(string $ip): string
    {
        // Normalize IPv6 localhost to IPv4
        if ($ip === '::1') {
            return '127.0.0.1';
        }

        // Handle IPv4-mapped IPv6 addresses (::ffff:127.0.0.1)
        if (str_starts_with($ip, '::ffff:')) {
            return substr($ip, 7);
        }

        return $ip;
    }

    /**
     * Check if an IP is whitelisted as a coaching IP.
     * Queries database directly (no cache) for real-time accuracy.
     */
    public function isWhitelistedIp(string $ip): bool
    {
        // Query database directly for real-time check
        return CoachingIp::isCoachingIp($ip);
    }

    /**
     * Get list of whitelisted coaching IPs (cached).
     *
     * @return array<string>
     */
    public function getWhitelistedIps(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return CoachingIp::getActiveIps()->toArray();
        });
    }

    /**
     * Clear the coaching IPs cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get the access mode for the current request.
     */
    public function getAccessMode(?Request $request = null): string
    {
        return $this->isCoachingAccess($request) ? 'coaching' : 'external';
    }
}
