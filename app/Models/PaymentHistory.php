<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $table = 'payment_history';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'package_id',
        'package_price',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_date',
        'due_date',
        'expiry_date',
        'payment_method',
        'transaction_note',
        'assigned_by',
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Status Methods
    public function isDue(): bool
    {
        return $this->payment_status === 'due' ||
            ($this->due_amount > 0 && $this->due_date && $this->due_date->isPast());
    }

    public function isPartial(): bool
    {
        return $this->payment_status === 'partial' &&
            $this->due_amount > 0 &&
            (! $this->due_date || $this->due_date->isFuture());
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' && $this->due_amount == 0;
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopeDue($query)
    {
        return $query->where('payment_status', 'due')
            ->orWhere(function ($q) {
                $q->where('due_amount', '>', 0)
                    ->where('due_date', '<', now());
            });
    }
}
