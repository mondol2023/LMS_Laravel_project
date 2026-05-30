<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPackage extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPackageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'duration',
        'price',
        'discount',
        'discount_type',
        'discount_till',
        'is_active',
        'full_mock_test_limit',
        'partial_reading_limit',
        'partial_writing_limit',
        'partial_listening_limit',
        'partial_speaking_limit',
        'practice_reading_enabled',
        'practice_listening_enabled',
        'practice_writing_enabled',
        'practice_writing_limit',
        'practice_speaking_enabled',
        'practice_speaking_limit',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'discount_till' => 'datetime',
            'is_active' => 'boolean',
            'full_mock_test_limit' => 'integer',
            'partial_reading_limit' => 'integer',
            'partial_writing_limit' => 'integer',
            'partial_listening_limit' => 'integer',
            'partial_speaking_limit' => 'integer',
            'practice_reading_enabled' => 'boolean',
            'practice_listening_enabled' => 'boolean',
            'practice_writing_enabled' => 'boolean',
            'practice_writing_limit' => 'integer',
            'practice_speaking_enabled' => 'boolean',
            'practice_speaking_limit' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'package_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'package_id')->where('is_active', true);
    }

    public function getFinalPriceAttribute(): float
    {
        if (! $this->discount) {
            return (float) $this->price;
        }

        if ($this->discount_type === 'percent') {
            return (float) $this->price - ($this->price * $this->discount / 100);
        }

        return (float) $this->price - $this->discount;
    }

    public function isDiscountValid(): bool
    {
        if (! $this->discount_till) {
            return true;
        }

        return $this->discount_till->isFuture();
    }

    // Get expiry date based on duration in months
    public function getExpiryDate(): string
    {
        return now()->addMonths($this->duration)->toDateString();
    }

    // Get formatted duration
    public function getFormattedDurationAttribute(): string
    {
        return $this->duration.' '.str('month')->plural($this->duration);
    }
}
