<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralInvoice extends Model
{
    protected $fillable = [
        'remote_invoice_id',
        'invoice_number',
        'subtotal',
        'discount',
        'total',
        'due_date',
        'status',
        'data',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'due_date' => 'date',
            'data' => 'array',
            'received_at' => 'datetime',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || ($this->status === 'unpaid' && $this->due_date?->isPast());
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['unpaid', 'overdue']);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
