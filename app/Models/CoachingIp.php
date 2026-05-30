<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachingIp extends Model
{
    /** @use HasFactory<\Database\Factories\CoachingIpFactory> */
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if an IP address is whitelisted as a coaching IP.
     */
    public static function isCoachingIp(string $ip): bool
    {
        // Normalize IP address
        $normalizedIp = self::normalizeIp($ip);

        return static::where('ip_address', $normalizedIp)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Normalize IP address for consistent comparison.
     */
    protected static function normalizeIp(string $ip): string
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
     * Get all active coaching IPs.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function getActiveIps(): \Illuminate\Support\Collection
    {
        return static::where('is_active', true)
            ->pluck('ip_address');
    }
}
