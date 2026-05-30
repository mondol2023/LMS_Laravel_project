<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'exam_id',
        'section',
        'part',
        'question_key',
        'answer',
        'last_saved_at',
    ];

    protected $casts = [
        'last_saved_at' => 'datetime',
    ];

    /**
     * Save or update a draft answer
     */
    public static function saveDraft(string $phone, string $examId, string $section, string $part, string $questionKey, ?string $answer): self
    {
        return self::updateOrCreate(
            [
                'phone' => $phone,
                'exam_id' => $examId,
                'section' => $section,
                'part' => $part,
                'question_key' => $questionKey,
            ],
            [
                'answer' => $answer,
                'last_saved_at' => now(),
            ]
        );
    }

    /**
     * Get all drafts for a specific phone and exam
     */
    public static function getDrafts(string $phone, string $examId): array
    {
        $drafts = self::where('phone', $phone)
            ->where('exam_id', $examId)
            ->get()
            ->groupBy(['section', 'part']);

        $result = [];
        foreach ($drafts as $section => $parts) {
            foreach ($parts as $part => $questions) {
                foreach ($questions as $draft) {
                    $result[$section][$part][$draft->question_key] = $draft->answer;
                }
            }
        }

        return $result;
    }

    /**
     * Get drafts for a specific section
     */
    public static function getSectionDrafts(string $phone, string $examId, string $section): array
    {
        $drafts = self::where('phone', $phone)
            ->where('exam_id', $examId)
            ->where('section', $section)
            ->get()
            ->groupBy('part');

        $result = [];
        foreach ($drafts as $part => $questions) {
            foreach ($questions as $draft) {
                $result[$part][$draft->question_key] = $draft->answer;
            }
        }

        return $result;
    }

    /**
     * Clear all drafts for a specific phone and exam
     */
    public static function clearDrafts(string $phone, string $examId): bool
    {
        return self::where('phone', $phone)
            ->where('exam_id', $examId)
            ->delete() > 0;
    }

    /**
     * Clear drafts for a specific section
     */
    public static function clearSectionDrafts(string $phone, string $examId, string $section): bool
    {
        return self::where('phone', $phone)
            ->where('exam_id', $examId)
            ->where('section', $section)
            ->delete() > 0;
    }
}
