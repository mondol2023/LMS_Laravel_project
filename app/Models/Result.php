<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'user_id',
        'local_exam_id',
        'username',
        'phone',
        'email',
        'passport',
        'exam_type',
        'modules',
        'reading',
        'writing',
        'listening',
        'speaking',
        'data',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'reading' => 'array',
            'writing' => 'array',
            'listening' => 'array',
            'speaking' => 'array',
            'data' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modificationLogs(): HasMany
    {
        return $this->hasMany(ResultModificationLog::class);
    }
}
