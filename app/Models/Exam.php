<?php

namespace App\Models;

use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'exam_id',
        'user_exam_id',
        'uuid',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($exam) {
            if (empty($exam->uuid)) {
                $exam->uuid = Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [];
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
