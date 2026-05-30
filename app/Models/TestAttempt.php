<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_id',
        'attempt_number',
        'status',
        'started_at',
        'completed_at',
        'listening_score',
        'reading_score',
        'writing_score',
        'speaking_score',
        'overall_band',
        'time_spent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'listening_score' => 'decimal:1',
            'reading_score' => 'decimal:1',
            'writing_score' => 'decimal:1',
            'speaking_score' => 'decimal:1',
            'overall_band' => 'decimal:1',
            'time_spent' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'attempt_id');
    }

    public function leaderboardEntry()
    {
        return $this->hasOne(LeaderboardEntry::class, 'attempt_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function getTotalTimeSpent(): int
    {
        return array_sum($this->time_spent ?? []);
    }

    public function calculateOverallBand(): float
    {
        $scores = array_filter([
            $this->listening_score,
            $this->reading_score,
            $this->writing_score,
            $this->speaking_score,
        ]);

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
    }
}
