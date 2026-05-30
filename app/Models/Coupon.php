<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    /** @use HasFactory<\Database\Factories\CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'discount',
        'discount_type',
        'max_uses',
        'current_uses',
        'discount_till',
    ];

    protected function casts(): array
    {
        return [
            'discount' => 'decimal:2',
            'max_uses' => 'integer',
            'current_uses' => 'integer',
            'discount_till' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        if ($this->discount_till && $this->discount_till->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function incrementUses(): void
    {
        $this->increment('current_uses');
    }
}
