<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeResult extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'exam_name',
        'score',
        'total',
        'band_score',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'total' => 'integer',
            'band_score' => 'decimal:1',
            'details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
