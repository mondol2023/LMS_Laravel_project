<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'payment_id',
        'subscribed_at',
        'expires_at',
        'is_active',
        'full_mock_tests_used',
        'partial_reading_used',
        'partial_writing_used',
        'partial_listening_used',
        'partial_speaking_used',
        'practice_writing_used',
        'practice_speaking_used',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'full_mock_tests_used' => 'integer',
            'partial_reading_used' => 'integer',
            'partial_writing_used' => 'integer',
            'partial_listening_used' => 'integer',
            'partial_speaking_used' => 'integer',
            'practice_writing_used' => 'integer',
            'practice_speaking_used' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PaymentHistory::class, 'payment_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }
}
