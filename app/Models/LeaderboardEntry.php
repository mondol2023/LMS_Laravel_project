<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_id',
        'attempt_id',
        'overall_band',
        'completion_time',
    ];

    protected function casts(): array
    {
        return [
            'overall_band' => 'decimal:1',
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

    public function testAttempt()
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }

    public function getFormattedCompletionTime(): string
    {
        $hours = floor($this->completion_time / 3600);
        $minutes = floor(($this->completion_time % 3600) / 60);
        $seconds = $this->completion_time % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function getRank(?int $testId = null): int
    {
        $query = static::query()
            ->where('overall_band', '>', $this->overall_band)
            ->orWhere(function ($q) {
                $q->where('overall_band', $this->overall_band)
                    ->where('completion_time', '<', $this->completion_time);
            });

        if ($testId) {
            $query->where('test_id', $testId);
        }

        return $query->count() + 1;
    }
}
