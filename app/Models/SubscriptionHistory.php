<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    const UPDATED_AT = null; // Only has created_at

    protected $fillable = [
        'user_id',
        'old_package_id',
        'new_package_id',
        'action',
        'performed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oldPackage(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'old_package_id');
    }

    public function newPackage(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'new_package_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
