<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'section',
        'question_number',
        'user_answer',
        'is_correct',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_earned' => 'decimal:1',
        ];
    }

    public function testAttempt()
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }

    public function isListening(): bool
    {
        return $this->section === 'listening';
    }

    public function isReading(): bool
    {
        return $this->section === 'reading';
    }

    public function isWriting(): bool
    {
        return $this->section === 'writing';
    }

    public function isSpeaking(): bool
    {
        return $this->section === 'speaking';
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->section, ['listening', 'reading']);
    }
}
