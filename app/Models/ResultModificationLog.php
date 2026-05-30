<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultModificationLog extends Model
{
    protected $fillable = [
        'result_id',
        'user_id',
        'modification_type',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
