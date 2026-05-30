<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralSetting extends Model
{
    protected $fillable = [
        'seller_status',
        'status_message',
        'status_reason',
        'grace_period_days',
        'auto_suspend_after_days',
        'last_status_update',
    ];

    protected function casts(): array
    {
        return [
            'last_status_update' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::first() ?? static::create([
            'seller_status' => 'active',
            'status_message' => 'Your account is active.',
        ]);
    }

    public function isActive(): bool
    {
        return $this->seller_status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->seller_status === 'suspended';
    }

    public function isBlocked(): bool
    {
        return $this->seller_status === 'blocked';
    }

    public function updateStatus(string $status, ?string $message = null, ?string $reason = null): void
    {
        $this->update([
            'seller_status' => $status,
            'status_message' => $message ?? $this->getDefaultMessage($status),
            'status_reason' => $reason,
            'last_status_update' => now(),
        ]);
    }

    private function getDefaultMessage(string $status): string
    {
        return match ($status) {
            'active' => 'Your account is active.',
            'suspended' => 'Your account has been suspended. Please contact support.',
            'blocked' => 'Your account has been blocked. Please contact support.',
            default => 'Unknown status.',
        };
    }
}
