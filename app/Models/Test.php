<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'difficulty',
        'status',
        'listening_content',
        'reading_content',
        'writing_tasks',
        'speaking_tasks',
    ];

    protected function casts(): array
    {
        return [
            'listening_content' => 'array',
            'reading_content' => 'array',
            'writing_tasks' => 'array',
            'speaking_tasks' => 'array',
        ];
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function leaderboardEntries()
    {
        return $this->hasMany(LeaderboardEntry::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function getAverageScore(): float
    {
        return $this->testAttempts()
            ->whereNotNull('overall_band')
            ->avg('overall_band') ?? 0;
    }

    public function getCompletedAttemptsCount(): int
    {
        return $this->testAttempts()
            ->where('status', 'completed')
            ->count();
    }
}
