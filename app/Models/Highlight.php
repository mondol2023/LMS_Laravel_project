<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'exam_id',
        'section',
        'part',
        'text',
        'color',
        'start_offset',
        'end_offset',
    ];

    protected function casts(): array
    {
        return [
            'start_offset' => 'integer',
            'end_offset' => 'integer',
        ];
    }

    /**
     * Get all highlights for a specific user, exam, section, and part
     */
    public static function getHighlights(string $phone, string $examId, string $section, string $part): array
    {
        return self::where('phone', $phone)
            ->where('exam_id', $examId)
            ->where('section', $section)
            ->where('part', $part)
            ->orderBy('start_offset')
            ->get()
            ->toArray();
    }

    /**
     * Save a new highlight
     */
    public static function saveHighlight(
        string $phone,
        string $examId,
        string $section,
        string $part,
        string $text,
        string $color,
        int $startOffset,
        int $endOffset
    ): self {
        return self::create([
            'phone' => $phone,
            'exam_id' => $examId,
            'section' => $section,
            'part' => $part,
            'text' => $text,
            'color' => $color,
            'start_offset' => $startOffset,
            'end_offset' => $endOffset,
        ]);
    }

    /**
     * Clear all highlights for a specific user, exam, section, and part
     */
    public static function clearHighlights(string $phone, string $examId, string $section, string $part): bool
    {
        return self::where('phone', $phone)
            ->where('exam_id', $examId)
            ->where('section', $section)
            ->where('part', $part)
            ->delete() > 0;
    }
}
